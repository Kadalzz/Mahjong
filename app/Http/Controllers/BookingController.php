<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MahjongTable;
use App\Models\Transaction;
use App\Services\DokuService;
use App\Services\TableDeviceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    private const START_GRACE_MINUTES = 5;

    public function __construct(
        private DokuService $doku,
        private TableDeviceService $device,
    ) {
    }

    
    public function index()
    {
        $tables = MahjongTable::with('pricing')
            ->where('status', '!=', 'maintenance')
            ->get();

        return view('booking.index', compact('tables'));
    }

    
    public function create(MahjongTable $table)
    {
        $table->load('pricing');

        if ($table->status === 'maintenance') {
            return redirect()->route('booking.index')
                ->with('error', 'Meja ini sedang dalam maintenance.');
        }

        return view('booking.create', compact('table'));
    }

    
    public function checkAvailability(Request $request, MahjongTable $table)
    {
        $request->validate([
            'booking_date'   => 'required|date|after_or_equal:today',
            'start_time'     => 'nullable|date_format:H:i',
            'duration_hours' => 'required|integer|min:1|max:8',
        ]);

        $busy = $table->busySlotsOn($request->booking_date);

        if (!$request->filled('start_time')) {
            return response()->json(['available' => false, 'reason' => 'incomplete', 'busy' => $busy]);
        }

        $durationHours = (int) $request->duration_hours;
        $problem       = $this->slotProblem($table, $request->booking_date, $request->start_time, $durationHours);
        $price         = $table->getCurrentPricePerHour() * $durationHours;

        return response()->json([
            'available'       => $problem === null,
            'reason'          => $problem['reason'] ?? null,
            'message'         => $problem['message'] ?? null,
            'end_time'        => Booking::computeEndTime($request->start_time, $durationHours),
            'ends_next_day'   => !Booking::startDateTime($request->booking_date, $request->start_time)
                ->addHours($durationHours)
                ->isSameDay(Carbon::parse($request->booking_date)),
            'price'           => $price,
            'price_formatted' => 'Rp ' . number_format($price, 0, ',', '.'),
            'busy'            => $busy,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mahjong_table_id' => 'required|exists:mahjong_tables,id',
            'customer_name'    => 'required|string|max:100',
            'customer_phone'   => 'required|digits_between:9,13',
            'booking_date'     => 'required|date|after_or_equal:today',
            'start_time'       => 'required|date_format:H:i',
            'duration_hours'   => 'required|integer|min:1|max:8',
        ]);

        $durationHours = (int) $request->duration_hours;
        $startTime     = $request->start_time;

        $result = DB::transaction(function () use ($request, $durationHours, $startTime) {
            $table = MahjongTable::with('pricing')->lockForUpdate()->findOrFail($request->mahjong_table_id);

            $problem = $this->slotProblem($table, $request->booking_date, $startTime, $durationHours);
            if ($problem !== null) {
                return $problem;
            }

            return Booking::create([
                'mahjong_table_id' => $table->id,
                'customer_name'    => $request->customer_name,
                'customer_phone'   => $request->customer_phone,
                'booking_date'     => $request->booking_date,
                'start_time'       => $startTime,
                'duration_hours'   => $durationHours,
                'end_time'         => Booking::computeEndTime($startTime, $durationHours),
                'total_price'      => $table->getCurrentPricePerHour() * $durationHours,
                'status'           => 'pending_payment',
                'booking_code'     => Booking::generateCode(),
            ]);
        });

        if (is_array($result)) {
            return back()->withInput()->withErrors(['start_time' => $result['message']]);
        }

        $booking = $result;
        $orderId = 'MJG-' . $booking->id . '-' . time();
        $invoice = $this->doku->createInvoice($booking, $orderId);

        if (!$invoice) {
            $booking->update(['status' => 'cancelled']);

            return back()->withInput()
                ->with('error', 'Pembayaran sedang tidak bisa dibuat. Silakan coba lagi sebentar.');
        }

        $booking->update([
            'payment_order_id' => $invoice['order_id'],
            'payment_url'      => $invoice['invoice_url'],
        ]);

        return redirect()->route('booking.confirm', $booking->booking_code);
    }

    public function confirm(string $code)
    {
        $booking = Booking::with('table')->where('booking_code', $code)->firstOrFail();
        return view('booking.confirm', compact('booking'));
    }

    public function status(string $code)
    {
        $booking = Booking::where('booking_code', $code)->firstOrFail();

        return response()->json([
            'status'          => $booking->status,
            'payment_expired' => $booking->isPaymentExpired(),
        ]);
    }

    
    public function invoice(string $code)
    {
        $booking = Booking::with(['table', 'transaction'])->where('booking_code', $code)->firstOrFail();

        if (!in_array($booking->status, ['active', 'done'])) {
            abort(404);
        }

        return view('booking.invoice', compact('booking'));
    }

    
    public function lookup(Request $request)
    {
        $bookings = collect();
        $searched = $request->filled('q');

        if ($searched) {
            $query = trim($request->q);
            $digits = preg_replace('/\D/', '', $query);

            if (preg_match('/^MJG-[A-Z0-9]+$/i', $query)) {
                $bookings = Booking::with('table')
                    ->where('booking_code', strtoupper($query))
                    ->get();
            } elseif (strlen($digits) >= 6 && strlen($digits) >= strlen(preg_replace('/[\s\-\+]/', '', $query))) {
                $suffix = substr($digits, -9);
                $bookings = Booking::with('table')
                    ->where('customer_phone', 'like', "%{$suffix}")
                    ->latest()
                    ->limit(20)
                    ->get();
            } else {
                $bookings = Booking::with('table')
                    ->whereRaw('LOWER(customer_name) LIKE ?', ['%' . strtolower($query) . '%'])
                    ->latest()
                    ->limit(20)
                    ->get();
            }
        }

        return view('booking.lookup', compact('bookings', 'searched'));
    }

    public function webhook(Request $request)
    {
        if (!$this->doku->verifyWebhookSignature($request)) {
            Log::warning('DOKU webhook rejected: invalid signature.');
            return response()->json(['status' => 'unauthorized'], 401);
        }

        try {
            $payload       = $request->all();
            $invoiceNumber = $payload['order']['invoice_number'] ?? null;
            $status        = $payload['transaction']['status'] ?? null;

            $booking = Booking::with('table')->where('payment_order_id', $invoiceNumber)->firstOrFail();

            if ($status === 'SUCCESS') {
                $this->handlePaymentSuccess($booking, $payload);
            } elseif ($status === 'FAILED' && $booking->status === 'pending_payment') {
                $booking->update(['status' => 'cancelled']);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('DOKU webhook error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    private function handlePaymentSuccess(Booking $booking, array $payload): void
    {
        $activated = DB::transaction(function () use ($booking, $payload) {
            MahjongTable::lockForUpdate()->find($booking->mahjong_table_id);
            $booking = Booking::with('table')->lockForUpdate()->find($booking->id);

            Transaction::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount'                 => $payload['order']['amount'] ?? $booking->total_price,
                    'payment_method'         => $payload['channel']['id'] ?? $payload['service']['id'] ?? null,
                    'gateway_transaction_id' => $payload['transaction']['original_request_id'] ?? null,
                    'gateway_status'         => 'SUCCESS',
                    'gateway_payload'        => $payload,
                    'paid_at'                => now(),
                ]
            );

            if ($booking->status === 'active') {
                return null;
            }

            $expiredBySystem = $booking->status === 'cancelled' && $booking->cancelled_at === null;
            $slotStillFree   = $booking->table->isAvailableAt(
                $booking->booking_date->toDateString(),
                $booking->start_time,
                $booking->duration_hours,
                $booking->id
            );

            if (($booking->status === 'pending_payment' || $expiredBySystem) && $slotStillFree && now()->lt($booking->endsAt())) {
                $booking->update(['status' => 'active']);
                return $booking;
            }

            $booking->update([
                'status'        => 'cancelled',
                'refund_status' => 'pending_refund',
                'cancelled_at'  => $booking->cancelled_at ?? now(),
            ]);

            Log::warning("DOKU payment received for {$booking->booking_code} but the slot is no longer available; manual refund needed.");

            return null;
        });

        if ($activated instanceof Booking) {
            $this->device->activate($activated->table, $activated);
        }
    }

    private function slotProblem(MahjongTable $table, string $date, string $startTime, int $durationHours): ?array
    {
        $startsAt = Booking::startDateTime($date, $startTime);
        if ($startsAt->lt(now()->subMinutes(self::START_GRACE_MINUTES)->startOfMinute())) {
            return ['reason' => 'past', 'message' => 'Jam mulai sudah lewat. Pilih jam yang akan datang atau tekan Main Sekarang.'];
        }

        if (!$table->isAvailableAt($date, $startTime, $durationHours)) {
            return ['reason' => 'conflict', 'message' => 'Jam ini bentrok dengan booking lain di meja ini. Silakan pilih jam lain.'];
        }

        return null;
    }
}

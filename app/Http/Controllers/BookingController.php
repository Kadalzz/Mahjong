<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MahjongTable;
use App\Models\Transaction;
use App\Services\DokuService;
use App\Services\TableDeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
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
            'start_time'     => 'required|date_format:H:i',
            'duration_hours' => 'required|integer|min:1|max:8',
        ]);

        $available = $table->isAvailableAt(
            $request->booking_date,
            $request->start_time,
            $request->duration_hours
        );

        $endTime = date('H:i', strtotime($request->start_time) + ($request->duration_hours * 3600));
        $price   = $table->getCurrentPricePerHour() * $request->duration_hours;

        return response()->json([
            'available' => $available,
            'end_time'  => $endTime,
            'price'     => $price,
            'price_formatted' => 'Rp ' . number_format($price, 0, ',', '.'),
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
        $endTime       = date('H:i', strtotime($startTime) + ($durationHours * 3600));

        
        
        
        $booking = DB::transaction(function () use ($request, $durationHours, $startTime, $endTime) {
            $table      = MahjongTable::with('pricing')->lockForUpdate()->findOrFail($request->mahjong_table_id);
            $totalPrice = $table->getCurrentPricePerHour() * $durationHours;

            $isAvailable = $table->isAvailableAt($request->booking_date, $startTime, $durationHours);
            $status      = $isAvailable ? 'pending_payment' : 'waiting';

            return Booking::create([
                'mahjong_table_id' => $table->id,
                'customer_name'    => $request->customer_name,
                'customer_phone'   => $request->customer_phone,
                'booking_date'     => $request->booking_date,
                'start_time'       => $startTime,
                'duration_hours'   => $durationHours,
                'end_time'         => $endTime,
                'total_price'      => $totalPrice,
                'status'           => $status,
                'booking_code'     => Booking::generateCode(),
            ]);
        });

        $status = $booking->status;

        
        if ($status !== 'waiting') {
            $orderId = 'MJG-' . $booking->id . '-' . time();
            $invoice = $this->doku->createInvoice($booking, $orderId);

            if ($invoice) {
                $booking->update([
                    'payment_order_id' => $invoice['order_id'],
                    'payment_url'      => $invoice['invoice_url'],
                ]);
            }
        }

        return redirect()->route('booking.confirm', $booking->booking_code);
    }

    
    public function confirm(string $code)
    {
        $booking = Booking::with('table')->where('booking_code', $code)->firstOrFail();
        return view('booking.confirm', compact('booking'));
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

    public function cancel(string $code)
    {
        $booking = Booking::with(['table', 'transaction'])->where('booking_code', $code)->firstOrFail();

        if (!$booking->canBeCancelledByCustomer()) {
            return back()->with('error', 'Booking ini sudah tidak bisa dibatalkan.');
        }

        $wasActive    = $booking->status === 'active';
        $refundStatus = $booking->determineRefundStatus();

        $booking->update([
            'status'        => 'cancelled',
            'refund_status' => $refundStatus,
            'cancelled_at'  => now(),
        ]);

        if ($wasActive) {
            $this->device->deactivate($booking->table, $booking);
        }

        $this->promoteWaiting($booking);

        return redirect()->route('booking.confirm', $booking->booking_code)
            ->with('success', 'Booking berhasil dibatalkan.');
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
                $alreadyActive = $booking->status === 'active';

                $booking->update(['status' => 'active']);

                Transaction::updateOrCreate(
                    ['booking_id' => $booking->id],
                    [
                        'amount'                 => $payload['order']['amount'] ?? $booking->total_price,
                        'payment_method'         => $payload['channel']['id'] ?? $payload['service']['id'] ?? null,
                        'gateway_transaction_id' => $payload['transaction']['original_request_id'] ?? null,
                        'gateway_status'         => $status,
                        'gateway_payload'        => $payload,
                        'paid_at'                => now(),
                    ]
                );

                if (!$alreadyActive) {
                    $this->device->activate($booking->table, $booking);
                }
            } elseif ($status === 'FAILED') {
                $booking->update(['status' => 'cancelled']);
                $this->promoteWaiting($booking);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('DOKU webhook error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    private function promoteWaiting(Booking $cancelledBooking): void
    {
        $waiting = Booking::where('mahjong_table_id', $cancelledBooking->mahjong_table_id)
            ->whereDate('booking_date', $cancelledBooking->booking_date)
            ->where('status', 'waiting')
            ->where(function ($q) use ($cancelledBooking) {
                $q->where('start_time', '<', $cancelledBooking->end_time)
                  ->where('end_time', '>', $cancelledBooking->start_time);
            })
            ->orderBy('created_at')
            ->first();

        if ($waiting) {
            $orderId = 'MJG-' . $waiting->id . '-' . time();
            $invoice = $this->doku->createInvoice($waiting, $orderId);

            $waiting->update(array_filter([
                'status'           => 'pending_payment',
                'payment_order_id' => $invoice['order_id'] ?? null,
                'payment_url'      => $invoice['invoice_url'] ?? null,
            ]));
        }
    }
}

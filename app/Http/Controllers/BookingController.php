<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MahjongTable;
use App\Models\Transaction;
use App\Services\TableDeviceService;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(
        private XenditService $xendit,
        private TableDeviceService $device,
    ) {
    }

    /**
     * Show table selection page
     */
    public function index()
    {
        $tables = MahjongTable::with('pricing')
            ->where('status', '!=', 'maintenance')
            ->get();

        return view('booking.index', compact('tables'));
    }

    /**
     * Show booking form for a specific table
     */
    public function create(MahjongTable $table)
    {
        $table->load('pricing');

        if ($table->status === 'maintenance') {
            return redirect()->route('booking.index')
                ->with('error', 'Meja ini sedang dalam maintenance.');
        }

        return view('booking.create', compact('table'));
    }

    /**
     * Check availability (AJAX)
     */
    public function checkAvailability(Request $request, MahjongTable $table)
    {
        $request->validate([
            'booking_date'   => 'required|date|after_or_equal:today',
            'start_time'     => 'required',
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

    /**
     * Store booking & create Xendit payment invoice
     */
    public function store(Request $request)
    {
        $request->validate([
            'mahjong_table_id' => 'required|exists:mahjong_tables,id',
            'customer_name'    => 'required|string|max:100',
            'customer_phone'   => 'required|string|max:20',
            'booking_date'     => 'required|date|after_or_equal:today',
            'start_time'       => 'required',
            'duration_hours'   => 'required|integer|min:1|max:8',
        ]);

        $table          = MahjongTable::with('pricing')->findOrFail($request->mahjong_table_id);
        $durationHours  = (int) $request->duration_hours;
        $startTime      = $request->start_time;
        $endTime        = date('H:i', strtotime($startTime) + ($durationHours * 3600));
        $totalPrice     = $table->getCurrentPricePerHour() * $durationHours;

        // Check if table slot is available (auto waiting list if not)
        $isAvailable = $table->isAvailableAt($request->booking_date, $startTime, $durationHours);
        $status      = $isAvailable ? 'pending_payment' : 'waiting';

        $booking = Booking::create([
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

        // Only create a Xendit invoice for non-waiting bookings
        if ($status !== 'waiting') {
            $orderId = 'MJG-' . $booking->id . '-' . time();
            $invoice = $this->xendit->createInvoice($booking, $orderId);

            if ($invoice) {
                $booking->update([
                    'payment_order_id' => $invoice['order_id'],
                    'payment_url'      => $invoice['invoice_url'],
                ]);
            }
        }

        return redirect()->route('booking.confirm', $booking->booking_code);
    }

    /**
     * Show booking confirmation page
     */
    public function confirm(string $code)
    {
        $booking = Booking::with('table')->where('booking_code', $code)->firstOrFail();
        return view('booking.confirm', compact('booking'));
    }

    /**
     * Show a printable invoice (only available once a booking is paid)
     */
    public function invoice(string $code)
    {
        $booking = Booking::with(['table', 'transaction'])->where('booking_code', $code)->firstOrFail();

        if (!in_array($booking->status, ['active', 'done'])) {
            abort(404);
        }

        return view('booking.invoice', compact('booking'));
    }

    /**
     * Look up a customer's own bookings by phone number (no login, so this
     * is the only way back in once the booking code is lost).
     */
    public function lookup(Request $request)
    {
        $bookings = collect();
        $searched = $request->filled('phone');

        if ($searched) {
            $digits = preg_replace('/\D/', '', $request->phone);
            $suffix = substr($digits, -9);

            if ($suffix !== '') {
                $bookings = Booking::with('table')
                    ->where('customer_phone', 'like', "%{$suffix}")
                    ->latest()
                    ->limit(20)
                    ->get();
            }
        }

        return view('booking.lookup', compact('bookings', 'searched'));
    }

    /**
     * Handle Xendit invoice callback (webhook)
     */
    public function webhook(Request $request)
    {
        $token = $request->header('X-CALLBACK-TOKEN');

        if (empty($token) || !hash_equals((string) config('xendit.callback_token'), (string) $token)) {
            Log::warning('Xendit webhook rejected: invalid callback token.');
            return response()->json(['status' => 'unauthorized'], 401);
        }

        try {
            $payload    = $request->all();
            $externalId = $payload['external_id'] ?? null;
            $status     = $payload['status'] ?? null;

            $booking = Booking::with('table')->where('payment_order_id', $externalId)->firstOrFail();

            if ($status === 'PAID' || $status === 'SETTLED') {
                $alreadyActive = $booking->status === 'active';

                $booking->update(['status' => 'active']);

                Transaction::updateOrCreate(
                    ['booking_id' => $booking->id],
                    [
                        'amount'                 => $payload['paid_amount'] ?? $payload['amount'] ?? $booking->total_price,
                        'payment_method'         => $payload['payment_channel'] ?? $payload['payment_method'] ?? null,
                        'gateway_transaction_id' => $payload['id'] ?? null,
                        'gateway_status'         => $status,
                        'gateway_payload'        => $payload,
                        'paid_at'                => now(),
                    ]
                );

                // Xendit may re-send the same callback; only act once per booking.
                if (!$alreadyActive) {
                    $this->device->activate($booking->table, $booking);
                }
            } elseif (in_array($status, ['EXPIRED', 'FAILED'])) {
                $booking->update(['status' => 'cancelled']);
                $this->promoteWaiting($booking);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Xendit webhook error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Promote first waiting booking to active when a slot opens up
     */
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
            $invoice = $this->xendit->createInvoice($waiting, $orderId);

            $waiting->update(array_filter([
                'status'           => 'pending_payment',
                'payment_order_id' => $invoice['order_id'] ?? null,
                'payment_url'      => $invoice['invoice_url'] ?? null,
            ]));
        }
    }
}

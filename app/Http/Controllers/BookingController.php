<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MahjongTable;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class BookingController extends Controller
{
    public function __construct()
    {
        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;
    }

    /**
     * Midtrans requires customer_details.email; the booking form only collects
     * name & phone, so derive a placeholder from the phone number.
     */
    private function placeholderEmail(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone) . '@booking.mahjongclub.local';
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
     * Store booking & create Midtrans payment
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

        // Only create Midtrans payment for non-waiting bookings
        if ($status !== 'waiting') {
            try {
                $orderId = 'MJG-' . $booking->id . '-' . time();

                $params = [
                    'transaction_details' => [
                        'order_id'     => $orderId,
                        'gross_amount' => (int) $totalPrice,
                    ],
                    'customer_details' => [
                        'first_name' => $request->customer_name,
                        'phone'      => $request->customer_phone,
                        'email'      => $this->placeholderEmail($request->customer_phone),
                    ],
                    'item_details' => [
                        [
                            'id'       => 'TABLE-' . $table->id,
                            'price'    => (int) $table->getCurrentPricePerHour(),
                            'quantity' => $durationHours,
                            'name'     => $table->name . ' (' . $durationHours . ' jam)',
                        ],
                    ],
                    'callbacks' => [
                        'finish' => route('booking.confirm', $booking->booking_code),
                    ],
                ];

                $snapToken = Snap::getSnapToken($params);

                $booking->update([
                    'midtrans_order_id'    => $orderId,
                    'midtrans_token'       => $snapToken,
                    'midtrans_payment_url' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$snapToken}",
                ]);
            } catch (\Exception $e) {
                Log::error('Midtrans error: ' . $e->getMessage());
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
     * Handle Midtrans webhook notification
     */
    public function webhook(Request $request)
    {
        try {
            $notification = new Notification();

            $transactionStatus = $notification->transaction_status;
            $orderId           = $notification->order_id;
            $paymentType       = $notification->payment_type;
            $grossAmount       = $notification->gross_amount;
            $fraudStatus       = $notification->fraud_status ?? null;

            $booking = Booking::where('midtrans_order_id', $orderId)->firstOrFail();

            $paid = false;
            if ($transactionStatus === 'capture' && $fraudStatus === 'accept') {
                $paid = true;
            } elseif ($transactionStatus === 'settlement') {
                $paid = true;
            } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                $booking->update(['status' => 'cancelled']);
                $this->promoteWaiting($booking);
            }

            if ($paid) {
                $booking->update(['status' => 'active']);

                Transaction::updateOrCreate(
                    ['booking_id' => $booking->id],
                    [
                        'amount'                  => $grossAmount,
                        'payment_method'          => $paymentType,
                        'midtrans_transaction_id' => $notification->transaction_id,
                        'midtrans_status'         => $transactionStatus,
                        'midtrans_payload'        => $notification->getResponse(),
                        'paid_at'                 => now(),
                    ]
                );
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
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
            try {
                $orderId = 'MJG-' . $waiting->id . '-' . time();
                $params  = [
                    'transaction_details' => [
                        'order_id'     => $orderId,
                        'gross_amount' => (int) $waiting->total_price,
                    ],
                    'customer_details' => [
                        'first_name' => $waiting->customer_name,
                        'phone'      => $waiting->customer_phone,
                        'email'      => $this->placeholderEmail($waiting->customer_phone),
                    ],
                ];
                $snapToken = Snap::getSnapToken($params);
                $waiting->update([
                    'status'               => 'pending_payment',
                    'midtrans_order_id'    => $orderId,
                    'midtrans_token'       => $snapToken,
                    'midtrans_payment_url' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$snapToken}",
                ]);
            } catch (\Exception $e) {
                Log::error('Promote waiting error: ' . $e->getMessage());
            }
        }
    }
}

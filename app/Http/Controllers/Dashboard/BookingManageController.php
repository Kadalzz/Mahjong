<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\DokuService;
use App\Services\TableDeviceService;
use Illuminate\Http\Request;

class BookingManageController extends Controller
{
    public function __construct(
        private TableDeviceService $device,
        private DokuService $doku,
    ) {
    }

    public function index(Request $request)
    {
        $query = Booking::with('table')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('booking_date', $request->date);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', "%{$request->search}%")
                  ->orWhere('customer_phone', 'like', "%{$request->search}%")
                  ->orWhere('booking_code', 'like', "%{$request->search}%");
            });
        }

        $bookings = $query->paginate(20)->withQueryString();

        return view('dashboard.bookings', compact('bookings'));
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'required|in:active,done,cancelled',
        ]);

        $oldStatus = $booking->status;

        $updates = ['status' => $request->status];
        if ($request->status === 'cancelled') {
            $updates['refund_status'] = $booking->determineRefundStatus();
            $updates['cancelled_at']  = now();
        }
        $booking->update($updates);

        if ($request->status === 'active' && $oldStatus !== 'active') {
            $this->device->activate($booking->table, $booking);
        }

        if (in_array($request->status, ['done', 'cancelled']) && $oldStatus === 'active') {
            $this->device->deactivate($booking->table, $booking);
        }

        if ($request->status === 'done' && !$booking->transaction) {
            Transaction::create([
                'booking_id'     => $booking->id,
                'amount'         => $booking->total_price,
                'payment_method' => 'cash',
                'paid_at'        => now(),
                'notes'          => 'Pembayaran manual oleh admin',
            ]);
        }

        if ($request->status === 'cancelled' && in_array($oldStatus, ['active', 'pending_payment'])) {
            $waiting = Booking::where('mahjong_table_id', $booking->mahjong_table_id)
                ->whereDate('booking_date', $booking->booking_date)
                ->where('status', 'waiting')
                ->where(function ($q) use ($booking) {
                    $q->where('start_time', '<', $booking->end_time)
                      ->where('end_time', '>', $booking->start_time);
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

        return back()->with('success', "Status booking #{$booking->booking_code} berhasil diperbarui.");
    }
}

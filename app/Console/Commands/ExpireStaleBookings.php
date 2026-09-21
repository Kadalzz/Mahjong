<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\XenditService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:expire-stale-bookings')]
#[Description('Cancel unpaid bookings past their Xendit invoice expiry, freeing the slot even if the payment webhook never arrives')]
class ExpireStaleBookings extends Command
{
    /**
     * Xendit invoices are created with a 1-hour expiry (see XenditService).
     * This runs a bit after that so the webhook gets first chance to act.
     */
    private const STALE_AFTER_MINUTES = 70;

    public function handle(XenditService $xendit): int
    {
        $stale = Booking::with('table')
            ->where('status', 'pending_payment')
            ->where('created_at', '<=', now()->subMinutes(self::STALE_AFTER_MINUTES))
            ->get();

        foreach ($stale as $booking) {
            $booking->update(['status' => 'cancelled']);
            $this->info("Dibatalkan (tidak dibayar): {$booking->booking_code}");

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
                $invoice = $xendit->createInvoice($waiting, $orderId);

                $waiting->update(array_filter([
                    'status'           => 'pending_payment',
                    'payment_order_id' => $invoice['order_id'] ?? null,
                    'payment_url'      => $invoice['invoice_url'] ?? null,
                ]));

                $this->info("  -> Waiting list dipromosikan: {$waiting->booking_code}");
            }
        }

        return self::SUCCESS;
    }
}

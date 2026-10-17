<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingOrder;
use App\Services\BookingOrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:expire-stale-bookings')]
#[Description('Cancel unpaid orders once their payment hold has passed, freeing the slots even if the payment webhook never arrives')]
class ExpireStaleBookings extends Command
{
    public function handle(BookingOrderService $orders): int
    {
        $expired = BookingOrder::where('status', 'pending_payment')
            ->where('created_at', '<=', now()->subMinutes(Booking::holdMinutes()))
            ->get();

        foreach ($expired as $order) {
            $orders->cancelUnpaid($order);
            $this->info("Dibatalkan (tidak dibayar): {$order->code}");
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:expire-stale-bookings')]
#[Description('Cancel unpaid bookings once their payment hold has passed, freeing the slot even if the payment webhook never arrives')]
class ExpireStaleBookings extends Command
{
    public function handle(): int
    {
        $expired = Booking::where('status', 'pending_payment')
            ->where('created_at', '<=', now()->subMinutes(Booking::holdMinutes()))
            ->get();

        foreach ($expired as $booking) {
            $booking->update(['status' => 'cancelled']);
            $this->info("Dibatalkan (tidak dibayar): {$booking->booking_code}");
        }

        return self::SUCCESS;
    }
}

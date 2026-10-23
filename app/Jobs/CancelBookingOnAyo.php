<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\Ayo\AyoSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelBookingOnAyo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Booking $booking)
    {
    }

    public function handle(AyoSyncService $ayo): void
    {
        if ($booking = $this->booking->fresh()) {
            $ayo->cancelBooking($booking);
        }
    }
}

<?php

namespace App\Observers;

use App\Jobs\CancelBookingOnAyo;
use App\Models\Booking;

class BookingObserver
{
    public function updated(Booking $booking): void
    {
        if (!$booking->wasChanged('status') || $booking->status !== 'cancelled') {
            return;
        }

        if ($booking->ayoReservations()->whereIn('status', ['active', 'retry', 'pushing', 'cancel_retry'])->exists()) {
            CancelBookingOnAyo::dispatch($booking)->afterCommit();
        }
    }
}

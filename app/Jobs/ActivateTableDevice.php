<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\TableDeviceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ActivateTableDevice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Booking $booking)
    {
    }

    public function handle(TableDeviceService $device): void
    {
        $booking = $this->booking->fresh('table');

        if (!$booking || $booking->status !== 'active') {
            return;
        }

        $device->activate($booking->table, $booking);
    }
}

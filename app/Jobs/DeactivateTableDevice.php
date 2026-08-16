<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\TableDeviceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeactivateTableDevice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Booking $booking)
    {
    }

    public function handle(TableDeviceService $device): void
    {
        $booking = $this->booking->fresh();

        if (!$booking || $booking->status !== 'active') {
            return;
        }

        $device->deactivate($booking->table, $booking);
    }
}

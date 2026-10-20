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

        if ($booking->source === 'staff') {
            $booking->update(['status' => 'done']);
        }

        $nextBookingRunning = Booking::where('mahjong_table_id', $booking->mahjong_table_id)
            ->where('id', '!=', $booking->id)
            ->where('status', 'active')
            ->where('source', '!=', 'block')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->exists();

        if ($nextBookingRunning) {
            return;
        }

        $device->deactivate($booking->table, $booking);
    }
}

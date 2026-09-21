<?php

namespace App\Jobs;

use App\Models\MahjongTable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EndTablePause implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private MahjongTable $table, private ?string $pausedUntil)
    {
    }

    public function handle(): void
    {
        $table = $this->table->fresh();

        // Only revert if this is still the same pause we scheduled - an
        // admin may have manually changed the status (or started a new,
        // longer pause) in the meantime.
        if (!$table || $table->status !== 'maintenance' || $table->paused_until === null) {
            return;
        }

        if ($table->paused_until->format('Y-m-d H:i:s') !== $this->pausedUntil) {
            return;
        }

        $table->update(['status' => 'available', 'paused_until' => null]);
    }
}

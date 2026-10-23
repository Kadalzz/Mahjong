<?php

namespace App\Console\Commands;

use App\Services\Ayo\AyoException;
use App\Services\Ayo\AyoSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-ayo')]
#[Description('Pull upcoming AYO bookings into the schedule and retry reservations that failed to reach AYO')]
class SyncAyo extends Command
{
    public function handle(AyoSyncService $ayo): int
    {
        if (!$ayo->enabled()) {
            $this->info('Integrasi AYO tidak aktif.');
            return self::SUCCESS;
        }

        try {
            $count = $ayo->syncUpcoming();
            $this->info("{$count} booking AYO diperiksa.");
        } catch (AyoException $e) {
            $this->error('Gagal mengambil booking AYO: ' . $e->getMessage());
        }

        $ayo->retryPending();

        return self::SUCCESS;
    }
}

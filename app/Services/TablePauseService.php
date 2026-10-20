<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\MahjongTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TablePauseService
{
    public const MINUTES = [2, 5, 10];

    public function __construct(private TableDeviceService $device)
    {
    }

    public function start(MahjongTable $table, int $minutes, User $user): Booking|string
    {
        if (!in_array($minutes, self::MINUTES, true)) {
            return 'Pilihan waktu tidak valid.';
        }

        $booking = DB::transaction(function () use ($table, $minutes, $user) {
            $table = MahjongTable::lockForUpdate()->findOrFail($table->id);

            if ($table->status === 'maintenance') {
                return 'Meja ini sedang dalam maintenance.';
            }

            if (empty($table->esp32_meja_id)) {
                return 'Meja ini belum punya perangkat (ID Meja ESP32).';
            }

            $startsAt = now();
            $endsAt   = $startsAt->copy()->addMinutes($minutes)->ceilMinute();

            if (!$table->isFreeBetween($startsAt, $endsAt)) {
                return $this->busyReason($table, $minutes);
            }

            return Booking::create([
                'source'           => 'staff',
                'created_by'       => $user->id,
                'mahjong_table_id' => $table->id,
                'customer_name'    => $user->name,
                'customer_phone'   => '',
                'duration_hours'   => 0,
                'duration_minutes' => $minutes,
                'starts_at'        => $startsAt,
                'ends_at'          => $endsAt,
                'total_price'      => 0,
                'status'           => 'active',
                'booking_code'     => 'JDA-' . strtoupper(bin2hex(random_bytes(4))),
            ]);
        });

        if (is_string($booking)) {
            return $booking;
        }

        $table = $booking->table;

        if (!$this->device->activate($table, $booking)) {
            $booking->update(['status' => 'cancelled']);

            return 'Gagal menyalakan meja. Cek koneksi ESP32 Master lalu coba lagi.';
        }

        ActivityLog::record($user, 'pause_start', $table, $minutes);

        return $booking;
    }

    public function stop(MahjongTable $table, User $user): bool|string
    {
        $booking = $this->runningPause($table);

        if (!$booking) {
            return 'Tidak ada Jeda yang sedang berjalan di meja ini.';
        }

        if (!$this->device->deactivate($table, $booking)) {
            return 'Gagal mematikan meja. Cek koneksi ESP32 Master lalu coba lagi.';
        }

        $booking->update(['status' => 'done']);

        ActivityLog::record($user, 'pause_stop', $table, $booking->duration_minutes);

        return true;
    }

    public function runningPause(MahjongTable $table): ?Booking
    {
        return $table->bookings()
            ->where('source', 'staff')
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->first();
    }

    public function stateFor(MahjongTable $table): array
    {
        $state = ['table' => $table, 'mode' => 'free', 'pause' => null, 'note' => null, 'options' => []];

        if ($table->status === 'maintenance') {
            $state['mode'] = 'blocked';
            $state['note'] = 'Meja sedang dalam maintenance.';

            return $state;
        }

        if (empty($table->esp32_meja_id)) {
            $state['mode'] = 'blocked';
            $state['note'] = 'Meja belum punya perangkat.';

            return $state;
        }

        if ($pause = $this->runningPause($table)) {
            $state['mode']  = 'paused';
            $state['pause'] = $pause;

            return $state;
        }

        if ($current = $table->currentBlockingBooking()) {
            $state['mode'] = 'blocked';
            $state['note'] = $current->status === 'pending_payment'
                ? 'Ada pesanan yang sedang menunggu pembayaran.'
                : 'Dipakai booking sampai ' . $current->endsAt()->format('H:i') . '.';

            return $state;
        }

        $startsAt = now();
        foreach (self::MINUTES as $minutes) {
            $state['options'][$minutes] = $table->isFreeBetween($startsAt, $startsAt->copy()->addMinutes($minutes)->ceilMinute());
        }

        if ($next = $table->nextBlockingBooking()) {
            if (in_array(false, $state['options'], true)) {
                $state['note'] = 'Booking berikutnya mulai ' . $next->startsAt()->format('H:i') . '.';
            }
        }

        return $state;
    }

    private function busyReason(MahjongTable $table, int $minutes): string
    {
        if ($current = $table->currentBlockingBooking()) {
            return $current->source === 'staff'
                ? 'Meja ini sedang dalam Jeda.'
                : 'Meja sedang dipakai booking sampai ' . $current->endsAt()->format('H:i') . '.';
        }

        $next = $table->nextBlockingBooking();

        return $next
            ? "Booking berikutnya mulai {$next->startsAt()->format('H:i')}, tidak cukup untuk {$minutes} menit."
            : 'Meja tidak tersedia saat ini.';
    }
}

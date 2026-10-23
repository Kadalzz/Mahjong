<?php

namespace App\Services\Ayo;

use App\Models\AyoReservation;
use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\MahjongTable;
use App\Models\Transaction;
use App\Services\TableDeviceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AyoSyncService
{
    public const BLOCKING_STATUSES = ['PENDING', 'SUCCESS', 'FINISHED'];

    public const PAID_STATUSES = ['SUCCESS', 'FINISHED'];

    public function __construct(
        private AyoClient $client,
        private TableDeviceService $device,
    ) {
    }

    public function enabled(): bool
    {
        return (bool) config('ayo.enabled') && $this->client->isConfigured();
    }

    public function syncUpcoming(): int
    {
        $from = now()->subDays((int) config('ayo.sync_days_back', 1))->startOfDay();
        $to   = now()->addDays((int) config('ayo.sync_days_ahead', 14))->startOfDay();

        $count = 0;
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $chunkEnd = $cursor->copy()->addDays(30)->min($to);

            foreach ($this->client->listBookings(['start_date' => $cursor->toDateString(), 'end_date' => $chunkEnd->toDateString()]) as $item) {
                $this->importBooking($item);
                $count++;
            }

            $cursor = $chunkEnd->copy()->addDay();
        }

        return $count;
    }

    public function refreshByBookingId(string $ayoBookingId): int
    {
        $items = $this->client->listBookings(['booking_id' => $ayoBookingId]);

        foreach ($items as $item) {
            $this->importBooking($item);
        }

        return count($items);
    }

    public function importBooking(array $item): ?Booking
    {
        $orderDetailId = (int) ($item['order_detail_id'] ?? 0);

        if ($orderDetailId === 0 || $this->isOwnReservation($item)) {
            return null;
        }

        $table = MahjongTable::where('ayo_field_id', (int) ($item['field_id'] ?? 0))->first();

        if (!$table) {
            return null;
        }

        $status = strtoupper((string) ($item['status'] ?? ''));
        [$startsAt, $endsAt] = $this->period($item);

        $outcome = DB::transaction(function () use ($table, $orderDetailId, $status, $startsAt, $endsAt, $item) {
            MahjongTable::lockForUpdate()->find($table->id);

            $booking  = Booking::where('ayo_order_detail_id', $orderDetailId)->lockForUpdate()->first();
            $wasPaid  = $booking && $booking->status === 'active' && in_array($booking->ayo_status, self::PAID_STATUSES, true);
            $blocking = in_array($status, self::BLOCKING_STATUSES, true);
            $paid     = in_array($status, self::PAID_STATUSES, true);

            if (!$booking && !$blocking) {
                return null;
            }

            $minutes = max(1, (int) round($startsAt->diffInMinutes($endsAt, true)));

            $attributes = [
                'source'           => 'ayo',
                'mahjong_table_id' => $table->id,
                'customer_name'    => $this->bookerName($item),
                'customer_phone'   => preg_replace('/\D/', '', (string) ($item['booker_phone'] ?? '')),
                'duration_hours'   => intdiv($minutes, 60),
                'duration_minutes' => $minutes,
                'starts_at'        => $startsAt,
                'ends_at'          => $endsAt,
                'total_price'      => (int) ($item['total_price'] ?? 0),
                'status'           => $blocking ? 'active' : 'cancelled',
                'ayo_status'       => $status,
            ];

            if ($blocking) {
                $attributes['ayo_conflict'] = !$table->isFreeBetween($startsAt, $endsAt, $booking?->id);
            }

            if ($booking) {
                $booking->fill($attributes)->save();
            } else {
                $booking = Booking::create($attributes + [
                    'ayo_order_detail_id' => $orderDetailId,
                    'booking_code'        => 'AYO-' . $orderDetailId,
                ]);
            }

            if ($paid) {
                Transaction::firstOrCreate(['booking_id' => $booking->id], [
                    'amount'         => (int) ($item['total_price'] ?? 0),
                    'payment_method' => 'ayo',
                    'paid_at'        => now(),
                    'notes'          => 'Booking AYO ' . ($item['booking_id'] ?? $orderDetailId),
                ]);
            } elseif ($status === 'CANCELLED') {
                Transaction::where('booking_id', $booking->id)->where('payment_method', 'ayo')->delete();
            }

            return ['booking' => $booking, 'was_paid' => $wasPaid, 'paid' => $paid];
        });

        if (!$outcome) {
            return null;
        }

        $booking = $outcome['booking'];

        if ($outcome['paid'] && !$outcome['was_paid']) {
            $this->device->activate($table, $booking);
        }

        if ($booking->status === 'cancelled' && $outcome['was_paid'] && now()->between($booking->startsAt(), $booking->endsAt()) && !$this->otherBookingRunning($booking)) {
            $this->device->deactivate($table, $booking);
        }

        return $booking;
    }

    public function pushOrder(BookingOrder $order): array
    {
        $result = ['status' => 'ok', 'conflicts' => []];

        if (!$this->enabled()) {
            return ['status' => 'skipped', 'conflicts' => []];
        }

        foreach ($order->bookings()->with('table')->get() as $booking) {
            $status = $this->pushBooking($booking);

            if ($status === 'conflict') {
                $result['status'] = 'conflict';
                $result['conflicts'][] = $booking;
            } elseif ($status === 'deferred' && $result['status'] === 'ok') {
                $result['status'] = 'deferred';
            }
        }

        return $result;
    }

    public function pushBooking(Booking $booking): string
    {
        if (!$this->enabled() || !$this->shouldPush($booking)) {
            return 'skipped';
        }

        $result = 'ok';

        foreach ($this->segments($booking) as $segment) {
            $reservation = AyoReservation::firstOrCreate(
                ['booking_id' => $booking->id, 'date' => $segment['date'], 'start_time' => $segment['start_time']],
                [
                    'field_id'    => $booking->table->ayo_field_id,
                    'end_time'    => $segment['end_time'],
                    'total_price' => $segment['price'],
                    'status'      => 'pushing',
                ]
            );

            if ($reservation->order_detail_id) {
                continue;
            }

            $outcome = $this->sendReservation($reservation, $booking);

            if ($outcome === 'conflict') {
                $result = 'conflict';
                break;
            }

            if ($outcome === 'deferred') {
                $result = 'deferred';
            }
        }

        if ($result === 'conflict') {
            $booking->update(['ayo_conflict' => true]);
            $this->cancelBooking($booking);
        }

        return $result;
    }

    public function cancelBooking(Booking $booking): void
    {
        $reservations = $booking->ayoReservations()
            ->whereIn('status', ['active', 'retry', 'pushing', 'cancel_retry'])
            ->get();

        foreach ($reservations as $reservation) {
            if (!$reservation->order_detail_id) {
                $reservation->update(['status' => 'cancelled']);
                continue;
            }

            try {
                $this->client->cancelReservation((int) $reservation->order_detail_id);
                $reservation->update(['status' => 'cancelled', 'last_error' => null]);
            } catch (AyoException $e) {
                $reservation->update(['status' => 'cancel_retry', 'last_error' => mb_substr($e->getMessage(), 0, 250)]);
                Log::warning("AYO cancel failed for {$booking->booking_code}: {$e->getMessage()}");
            }
        }
    }

    public function retryPending(): void
    {
        $stale = AyoReservation::with('booking.table')
            ->where(fn ($q) => $q->where('status', 'retry')
                ->orWhere(fn ($p) => $p->where('status', 'pushing')->where('updated_at', '<', now()->subMinutes(2))))
            ->get();

        foreach ($stale as $reservation) {
            $booking = $reservation->booking;

            if (!$booking || !$this->shouldPush($booking)) {
                $reservation->update(['status' => 'cancelled']);
                continue;
            }

            if ($this->sendReservation($reservation, $booking) === 'conflict') {
                $booking->update(['ayo_conflict' => true]);
            }
        }

        AyoReservation::with('booking')->where('status', 'cancel_retry')->get()
            ->groupBy('booking_id')
            ->each(fn ($group) => $group->first()->booking && $this->cancelBooking($group->first()->booking));
    }

    public function segments(Booking $booking): array
    {
        $cursor  = $booking->startsAt();
        $end     = $booking->endsAt();
        $total   = (int) $booking->total_price;
        $minutes = max(1, (int) round($cursor->diffInMinutes($end, true)));

        $segments  = [];
        $allocated = 0;

        while ($cursor->lt($end)) {
            $nextMidnight = $cursor->copy()->startOfDay()->addDay();
            $segmentEnd   = $end->lt($nextMidnight) ? $end->copy() : $nextMidnight;
            $isLast       = $segmentEnd->eq($end);
            $span         = (int) round($cursor->diffInMinutes($segmentEnd, true));
            $price        = $isLast ? $total - $allocated : (int) floor($total * $span / $minutes);
            $allocated   += $price;

            $segments[] = [
                'date'       => $cursor->toDateString(),
                'start_time' => $cursor->format('H:i:s'),
                'end_time'   => $segmentEnd->eq($nextMidnight) ? '24:00:00' : $segmentEnd->format('H:i:s'),
                'price'      => $price,
            ];

            $cursor = $segmentEnd;
        }

        return $segments;
    }

    private function sendReservation(AyoReservation $reservation, Booking $booking): string
    {
        if ($reservation->attempts > 0 && $this->adoptExisting($reservation, $booking)) {
            return 'ok';
        }

        $reservation->increment('attempts');

        try {
            $data = $this->client->createReservation(array_filter([
                'field_id'    => (int) $reservation->field_id,
                'date'        => $reservation->date,
                'start_time'  => $reservation->start_time,
                'end_time'    => $reservation->end_time,
                'total_price' => (int) $reservation->total_price,
                'user'        => $this->userPayload($booking),
            ], fn ($value) => $value !== null));

            $reservation->update([
                'order_detail_id' => (int) $data['order_detail_id'],
                'ayo_booking_id'  => $data['booking_id'] ?? null,
                'status'          => 'active',
                'last_error'      => null,
            ]);

            return 'ok';
        } catch (AyoException $e) {
            if ($e->isConflict() && $this->adoptExisting($reservation, $booking)) {
                return 'ok';
            }

            $conflict = $e->isConflict();

            $reservation->update([
                'status'     => $conflict ? 'failed' : 'retry',
                'last_error' => mb_substr($e->getMessage(), 0, 250),
            ]);

            Log::warning("AYO reservation failed for {$booking->booking_code}: {$e->getMessage()}");

            return $conflict ? 'conflict' : 'deferred';
        }
    }

    private function adoptExisting(AyoReservation $reservation, Booking $booking): bool
    {
        try {
            $items = $this->client->listBookings(['date' => $reservation->date]);
        } catch (AyoException) {
            return false;
        }

        $endTime      = $reservation->end_time === '24:00:00' ? '00:00:00' : $reservation->end_time;
        $expectedName = $this->userPayload($booking)['name'] ?? '-';
        $claimed      = AyoReservation::whereNotNull('order_detail_id')->pluck('order_detail_id')->map(fn ($id) => (int) $id)->all();

        foreach ($items as $item) {
            $orderDetailId = (int) ($item['order_detail_id'] ?? 0);

            $matches = ($item['booking_source'] ?? '') === 'reservation'
                && strtoupper((string) ($item['status'] ?? '')) !== 'CANCELLED'
                && (int) ($item['field_id'] ?? 0) === (int) $reservation->field_id
                && ($item['start_time'] ?? '') === $reservation->start_time
                && ($item['end_time'] ?? '') === $endTime
                && trim((string) ($item['booker_name'] ?? '')) === $expectedName
                && !in_array($orderDetailId, $claimed, true)
                && !Booking::where('ayo_order_detail_id', $orderDetailId)->exists();

            if ($matches) {
                $reservation->update([
                    'order_detail_id' => $orderDetailId,
                    'ayo_booking_id'  => $item['booking_id'] ?? null,
                    'status'          => 'active',
                    'last_error'      => null,
                ]);

                return true;
            }
        }

        return false;
    }

    private function shouldPush(Booking $booking): bool
    {
        return !in_array($booking->source, ['ayo', 'staff'], true)
            && in_array($booking->status, ['pending_payment', 'active'], true)
            && $booking->table?->ayo_field_id
            && $booking->endsAt()->isFuture();
    }

    private function isOwnReservation(array $item): bool
    {
        if (($item['booking_source'] ?? '') !== 'reservation') {
            return false;
        }

        $orderDetailId = (int) $item['order_detail_id'];

        if (AyoReservation::where('order_detail_id', $orderDetailId)->exists()) {
            return true;
        }

        return AyoReservation::whereNull('order_detail_id')
            ->whereIn('status', ['pushing', 'retry'])
            ->where('field_id', (int) ($item['field_id'] ?? 0))
            ->where('date', (string) ($item['date'] ?? ''))
            ->where('start_time', (string) ($item['start_time'] ?? ''))
            ->exists();
    }

    private function period(array $item): array
    {
        $startsAt = Carbon::parse($item['date'] . ' ' . $item['start_time']);
        $endsAt   = Carbon::parse($item['date'] . ' ' . $item['end_time']);

        if ($endsAt->lte($startsAt)) {
            $endsAt->addDay();
        }

        return [$startsAt, $endsAt];
    }

    private function bookerName(array $item): string
    {
        $name = trim((string) ($item['booker_name'] ?? ''));

        return $name === '' || $name === '-' ? 'Pelanggan AYO' : mb_substr($name, 0, 100);
    }

    private function userPayload(Booking $booking): ?array
    {
        $name  = trim(preg_replace("/[^\p{L}\p{N} .,'-]/u", '', (string) $booking->customer_name));
        $phone = preg_replace('/\D/', '', (string) $booking->customer_phone);

        if ($name === '' || strlen($phone) < 9) {
            return null;
        }

        if (!str_starts_with($phone, '0') && !str_starts_with($phone, '62')) {
            $phone = '0' . $phone;
        }

        return ['name' => $name, 'phone' => $phone];
    }

    private function otherBookingRunning(Booking $booking): bool
    {
        return Booking::where('mahjong_table_id', $booking->mahjong_table_id)
            ->where('id', '!=', $booking->id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->exists();
    }
}

<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\MahjongTable;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingOrderService
{
    public const START_GRACE_MINUTES = 5;

    public function __construct(private TableDeviceService $device)
    {
    }

    public function slotProblem(MahjongTable $table, string $date, string $startTime, int $durationHours, bool $allowPastStart = false): ?array
    {
        $startsAt = Booking::startDateTime($date, $startTime);
        $endsAt   = $startsAt->copy()->addHours($durationHours);

        if ($table->status === 'maintenance' && ($table->paused_until === null || $startsAt->lt($table->paused_until))) {
            return ['reason' => 'maintenance', 'message' => 'Meja ini sedang tidak bisa dipesan.'];
        }

        if ($allowPastStart ? $endsAt->lte(now()) : $startsAt->lt(now()->subMinutes(self::START_GRACE_MINUTES)->startOfMinute())) {
            return ['reason' => 'past', 'message' => 'Jam mulai sudah lewat. Pilih jam yang akan datang atau tekan Main Sekarang.'];
        }

        if (!$table->isAvailableAt($date, $startTime, $durationHours)) {
            return ['reason' => 'conflict', 'message' => 'Waktu yang kamu pilih sudah dipesan. Yuk, pilih jam lainnya!'];
        }

        return null;
    }

    public function create(array $details, array $lines, string $source, bool $allowPastStart = false): BookingOrder|array
    {
        $order = DB::transaction(function () use ($details, $lines, $source, $allowPastStart) {
            $tables = MahjongTable::with('pricing')
                ->whereIn('id', collect($lines)->pluck('table_id')->map(fn ($id) => (int) $id)->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $errors = [];
            foreach ($lines as $index => $line) {
                $table   = $tables[(int) $line['table_id']];
                $problem = $this->slotProblem($table, $details['booking_date'], $line['start_time'], (int) $line['duration_hours'], $allowPastStart);

                if ($problem !== null) {
                    $errors[$index] = "{$table->name}: {$problem['message']}";
                }
            }

            if ($errors) {
                return $errors;
            }

            $status = $source === 'customer' ? 'pending_payment' : 'active';

            $order = BookingOrder::create([
                'code'           => BookingOrder::generateCode(),
                'source'         => $source,
                'customer_name'  => $details['customer_name'],
                'customer_phone' => $details['customer_phone'] ?? null,
                'booking_date'   => $details['booking_date'],
                'status'         => $status,
                'notes'          => $details['notes'] ?? null,
            ]);

            $total = 0;
            foreach (array_values($lines) as $position => $line) {
                $table = $tables[(int) $line['table_id']];
                $hours = (int) $line['duration_hours'];
                $price = in_array($source, ['customer', 'cash'], true) ? $table->getCurrentPricePerHour() * $hours : 0;

                $booking = Booking::create([
                    'booking_order_id' => $order->id,
                    'source'           => $source,
                    'mahjong_table_id' => $table->id,
                    'customer_name'    => $order->customer_name,
                    'customer_phone'   => $order->customer_phone ?? '',
                    'booking_date'     => $details['booking_date'],
                    'start_time'       => $line['start_time'],
                    'duration_hours'   => $hours,
                    'end_time'         => Booking::computeEndTime($line['start_time'], $hours),
                    'total_price'      => $price,
                    'status'           => $status,
                    'booking_code'     => $order->code . '-' . ($position + 1),
                ]);

                if (in_array($source, ['owner', 'cash'], true)) {
                    Transaction::create([
                        'booking_id'     => $booking->id,
                        'amount'         => $price,
                        'payment_method' => $source,
                        'paid_at'        => now(),
                        'notes'          => $source === 'owner' ? 'Booking owner (gratis)' : 'Pembayaran tunai di kasir',
                    ]);
                }

                $total += $price;
            }

            $order->update(['total_price' => $total]);

            return $order;
        });

        if ($order instanceof BookingOrder && in_array($source, ['owner', 'cash'], true)) {
            foreach ($order->bookings()->with('table')->get() as $booking) {
                $this->device->activate($booking->table, $booking);
            }
        }

        return $order;
    }

    public function markPaid(BookingOrder $order, array $payload): void
    {
        $activated = DB::transaction(function () use ($order, $payload) {
            $order    = BookingOrder::lockForUpdate()->find($order->id);
            $bookings = $order->bookings()->with('table')->get();

            MahjongTable::whereIn('id', $bookings->pluck('mahjong_table_id')->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $activated = [];
            $needsRefund = false;

            foreach ($bookings as $booking) {
                Transaction::updateOrCreate(
                    ['booking_id' => $booking->id],
                    [
                        'amount'                 => $booking->total_price,
                        'payment_method'         => $payload['channel']['id'] ?? $payload['service']['id'] ?? null,
                        'gateway_transaction_id' => $payload['transaction']['original_request_id'] ?? null,
                        'gateway_status'         => 'SUCCESS',
                        'gateway_payload'        => $payload,
                        'paid_at'                => now(),
                    ]
                );

                if (in_array($booking->status, ['active', 'done'], true)) {
                    continue;
                }

                $expiredBySystem = $booking->status === 'cancelled' && $booking->cancelled_at === null;
                $slotStillFree   = $booking->table->isAvailableAt(
                    $booking->booking_date->toDateString(),
                    $booking->start_time,
                    $booking->duration_hours,
                    $booking->id
                );

                if (($booking->status === 'pending_payment' || $expiredBySystem) && $slotStillFree && now()->lt($booking->endsAt())) {
                    $booking->update(['status' => 'active']);
                    $activated[] = $booking;
                    continue;
                }

                $booking->update([
                    'status'        => 'cancelled',
                    'refund_status' => 'pending_refund',
                    'cancelled_at'  => $booking->cancelled_at ?? now(),
                ]);
                $needsRefund = true;

                Log::warning("DOKU payment received for {$booking->booking_code} but the slot is no longer available; manual refund needed.");
            }

            $order->update(array_filter([
                'status'        => $order->bookings()->whereIn('status', ['active', 'done'])->exists() ? 'active' : 'cancelled',
                'refund_status' => $needsRefund ? 'pending_refund' : null,
            ]));

            return $activated;
        });

        foreach ($activated as $booking) {
            $this->device->activate($booking->table, $booking);
        }
    }

    public function cancelUnpaid(BookingOrder $order): void
    {
        $order->update(['status' => 'cancelled']);
        $order->bookings()->where('status', 'pending_payment')->update(['status' => 'cancelled']);
    }

    public function syncOrderStatus(BookingOrder $order): void
    {
        $statuses = $order->bookings()->pluck('status');

        $status = match (true) {
            $statuses->contains('active')          => 'active',
            $statuses->contains('pending_payment') => 'pending_payment',
            $statuses->contains('done')            => 'done',
            default                                => 'cancelled',
        };

        $order->update(['status' => $status]);
    }
}

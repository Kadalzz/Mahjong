<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    public const REFUND_WINDOW_HOURS = 24;

    public const MAX_DURATION_HOURS = 48;

    protected $fillable = [
        'booking_order_id',
        'source',
        'mahjong_table_id',
        'customer_name',
        'customer_phone',
        'booking_date',
        'start_time',
        'duration_hours',
        'end_time',
        'starts_at',
        'ends_at',
        'total_price',
        'status',
        'refund_status',
        'cancelled_at',
        'booking_code',
        'payment_order_id',
        'payment_url',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_price' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Booking $booking) {
            if (!$booking->booking_date || !$booking->start_time || !$booking->duration_hours) {
                return;
            }

            $startsAt = self::startDateTime($booking->booking_date->toDateString(), $booking->start_time);

            $booking->starts_at = $startsAt;
            $booking->ends_at   = $startsAt->copy()->addHours((int) $booking->duration_hours);
            $booking->end_time  = $booking->ends_at->format('H:i');
        });
    }

    public static function startDateTime(string $date, string $startTime): Carbon
    {
        return Carbon::parse($date . ' ' . substr($startTime, 0, 5));
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(MahjongTable::class, 'mahjong_table_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(BookingOrder::class, 'booking_order_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_payment' => $this->isPaymentExpired() ? 'Waktu Bayar Habis' : 'Menunggu Pembayaran',
            'waiting'         => 'Waiting List',
            'active'          => 'Aktif',
            'done'            => 'Selesai',
            'cancelled'       => 'Dibatalkan',
            default           => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending_payment' => 'warning',
            'waiting'         => 'info',
            'active'          => 'success',
            'done'            => 'secondary',
            'cancelled'       => 'danger',
            default           => 'light',
        };
    }

    public static function generateCode(): string
    {
        do {
            $code = 'MJG-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (self::where('booking_code', $code)->exists() || BookingOrder::where('code', $code)->exists());

        return $code;
    }

    public function getRefundStatusLabelAttribute(): ?string
    {
        return match ($this->refund_status) {
            'refundable'     => 'Dana Dikembalikan',
            'non_refundable' => 'Tidak Dikembalikan',
            'pending_refund' => 'Perlu Refund Manual',
            default          => null,
        };
    }

    public static function holdMinutes(): int
    {
        return max(1, (int) config('doku.payment_due_minutes', 5));
    }

    public static function computeEndTime(string $startTime, int $durationHours): string
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($startTime, 0, 5)));
        $endMinutes = ($hour * 60 + $minute + $durationHours * 60) % (24 * 60);

        return sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);
    }

    public function endsNextDay(): bool
    {
        return $this->daysUntilEnd() > 0;
    }

    public function daysUntilEnd(): int
    {
        return (int) $this->startsAt()->startOfDay()->diffInDays($this->endsAt()->startOfDay());
    }

    public function getTimeRangeLabelAttribute(): string
    {
        $days = $this->daysUntilEnd();

        return substr($this->start_time, 0, 5) . ' – ' . $this->endsAt()->format('H:i')
            . ($days > 0 ? " (+{$days} hari)" : '');
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 'active')
              ->orWhere(function ($q2) {
                  $q2->where('status', 'pending_payment')
                     ->where('created_at', '>', now()->subMinutes(self::holdMinutes()));
              });
        });
    }

    public function holdExpiresAt(): Carbon
    {
        return $this->created_at->copy()->addMinutes(self::holdMinutes());
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === 'pending_payment' && now()->lt($this->holdExpiresAt());
    }

    public function isPaymentExpired(): bool
    {
        return $this->status === 'pending_payment' && !$this->isAwaitingPayment();
    }

    public function startsAt(): Carbon
    {
        return $this->starts_at?->copy()
            ?? self::startDateTime($this->booking_date->toDateString(), $this->start_time);
    }

    public function endsAt(): Carbon
    {
        return $this->ends_at?->copy()
            ?? $this->startsAt()->addHours((int) $this->duration_hours);
    }

    public function determineRefundStatus(): ?string
    {
        if (!$this->transaction) {
            return null;
        }

        $hoursUntilStart = ($this->startsAt()->timestamp - now()->timestamp) / 3600;

        return $hoursUntilStart >= self::REFUND_WINDOW_HOURS ? 'refundable' : 'non_refundable';
    }
}

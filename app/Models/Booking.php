<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    public const REFUND_WINDOW_HOURS = 24;

    protected $fillable = [
        'mahjong_table_id',
        'customer_name',
        'customer_phone',
        'booking_date',
        'start_time',
        'duration_hours',
        'end_time',
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
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(MahjongTable::class, 'mahjong_table_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_payment' => 'Menunggu Pembayaran',
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
            $code = 'MJG-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('booking_code', $code)->exists());

        return $code;
    }

    public function getRefundStatusLabelAttribute(): ?string
    {
        return match ($this->refund_status) {
            'refundable'     => 'Dana Dikembalikan',
            'non_refundable' => 'Tidak Dikembalikan',
            default          => null,
        };
    }

    public function canBeCancelledByCustomer(): bool
    {
        if (!in_array($this->status, ['pending_payment', 'waiting', 'active'])) {
            return false;
        }

        return now()->lt($this->startsAt());
    }

    public function startsAt(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->booking_date->toDateString() . ' ' . $this->start_time);
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

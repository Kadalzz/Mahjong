<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
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
        'booking_code',
        'payment_order_id',
        'payment_url',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_price' => 'decimal:2',
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

    /**
     * Generate a unique booking code
     */
    public static function generateCode(): string
    {
        do {
            $code = 'MJG-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('booking_code', $code)->exists());

        return $code;
    }
}

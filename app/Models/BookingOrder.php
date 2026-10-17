<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingOrder extends Model
{
    public const SOURCES = [
        'customer' => 'Pelanggan',
        'owner'    => 'Owner',
        'cash'     => 'Tunai',
        'block'    => 'Blokir',
    ];

    protected $fillable = [
        'code',
        'source',
        'customer_name',
        'customer_phone',
        'booking_date',
        'total_price',
        'status',
        'refund_status',
        'payment_order_id',
        'payment_url',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_price'  => 'decimal:2',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->orderBy('starts_at');
    }

    public static function generateCode(): string
    {
        do {
            $code = 'MJG-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (self::where('code', $code)->exists() || Booking::where('booking_code', $code)->exists());

        return $code;
    }

    public function holdExpiresAt(): Carbon
    {
        return $this->created_at->copy()->addMinutes(Booking::holdMinutes());
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === 'pending_payment' && now()->lt($this->holdExpiresAt());
    }

    public function isPaymentExpired(): bool
    {
        return $this->status === 'pending_payment' && !$this->isAwaitingPayment();
    }

    public function isPaid(): bool
    {
        return $this->bookings->contains(fn (Booking $b) => in_array($b->status, ['active', 'done']));
    }

    public function needsRefund(): bool
    {
        return $this->bookings->contains(fn (Booking $b) => $b->refund_status === 'pending_refund');
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_payment' => $this->isPaymentExpired() ? 'Waktu Bayar Habis' : 'Menunggu Pembayaran',
            'active'          => 'Aktif',
            'done'            => 'Selesai',
            'cancelled'       => 'Dibatalkan',
            default           => $this->status,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    public const TYPE_AMOUNT = 'amount';
    public const TYPE_PERCENT = 'percent';
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    public const MAX_PER_BATCH = 200;

    public const STATUSES = [
        'available' => 'Tersedia',
        'held'      => 'Menunggu bayar',
        'used'      => 'Terpakai',
        'expired'   => 'Kedaluwarsa',
    ];

    protected $fillable = [
        'code',
        'label',
        'discount_type',
        'discount_value',
        'max_discount',
        'expires_on',
        'booking_order_id',
        'used_at',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'max_discount'   => 'integer',
        'expires_on'     => 'date',
        'used_at'        => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(BookingOrder::class, 'booking_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function normalize(?string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string) $code));
    }

    public static function findByCode(?string $code): ?self
    {
        $code = self::normalize($code);

        return $code === '' ? null : self::where('code', $code)->first();
    }

    public static function generateCode(): string
    {
        do {
            $chars = '';
            for ($i = 0; $i < 8; $i++) {
                $chars .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $code = 'HZ-' . substr($chars, 0, 4) . '-' . substr($chars, 4);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function discountFor(int $subtotal): int
    {
        $discount = $this->discount_type === self::TYPE_PERCENT
            ? intdiv($subtotal * $this->discount_value, 100)
            : $this->discount_value;

        if ($this->discount_type === self::TYPE_PERCENT && $this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return max(0, min($discount, $subtotal));
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->lt(today());
    }

    public function isHeld(): bool
    {
        return $this->used_at === null && $this->booking_order_id !== null && (bool) $this->order?->isAwaitingPayment();
    }

    public function status(): string
    {
        return match (true) {
            $this->used_at !== null => 'used',
            $this->isHeld()         => 'held',
            $this->isExpired()      => 'expired',
            default                 => 'available',
        };
    }

    public function problem(): ?string
    {
        return match ($this->status()) {
            'used'    => 'Kode voucher ini sudah pernah dipakai.',
            'held'    => 'Kode voucher ini sedang dipakai di pesanan lain yang menunggu pembayaran. Coba lagi setelah ' . Booking::holdMinutes() . ' menit.',
            'expired' => 'Kode voucher ini sudah kedaluwarsa.',
            default   => null,
        };
    }

    public function getDiscountLabelAttribute(): string
    {
        if ($this->discount_type === self::TYPE_PERCENT) {
            return $this->discount_value . '%' . ($this->max_discount ? ' (maks. Rp ' . number_format($this->max_discount, 0, ',', '.') . ')' : '');
        }

        return 'Rp ' . number_format($this->discount_value, 0, ',', '.');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MahjongTable extends Model
{
    protected $fillable = ['name', 'capacity', 'status', 'description', 'esp32_meja_id', 'paused_until'];

    protected $casts = [
        'paused_until' => 'datetime',
    ];

    public function pricing(): HasOne
    {
        return $this->hasOne(Pricing::class)->where('is_active', true)->latest();
    }

    public function allPricing(): HasMany
    {
        return $this->hasMany(Pricing::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Check if the table is available for a given date + time slot
     */
    public function isAvailableAt(string $date, string $startTime, int $durationHours): bool
    {
        $endTime = date('H:i', strtotime($startTime) + ($durationHours * 3600));

        return !$this->bookings()
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending_payment', 'active', 'waiting'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    $q2->where('start_time', '<', $endTime)
                       ->where('end_time', '>', $startTime);
                });
            })
            ->exists();
    }

    public function getCurrentPricePerHour(): float
    {
        return $this->pricing?->price_per_hour ?? 0;
    }
}

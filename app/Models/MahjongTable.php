<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MahjongTable extends Model
{
    protected $fillable = ['name', 'capacity', 'status', 'description', 'esp32_meja_id', 'ayo_field_id', 'paused_until'];

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

    
    public function isAvailableAt(string $date, string $startTime, int $durationHours, ?int $exceptBookingId = null): bool
    {
        $startsAt = Booking::startDateTime($date, $startTime);

        return $this->isFreeBetween($startsAt, $startsAt->copy()->addHours($durationHours), $exceptBookingId);
    }

    public function isFreeBetween(Carbon $startsAt, Carbon $endsAt, ?int $exceptBookingId = null): bool
    {
        return !$this->bookings()
            ->blocking()
            ->when($exceptBookingId, fn ($q) => $q->where('id', '!=', $exceptBookingId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }

    public function bookingsOverlappingDay(string $date)
    {
        $dayStart = Carbon::parse($date)->startOfDay();

        return $this->bookings()
            ->blocking()
            ->where('starts_at', '<', $dayStart->copy()->addDay())
            ->where('ends_at', '>', $dayStart)
            ->orderBy('starts_at');
    }

    public function busySlotsOn(string $date)
    {
        $dayStart = Carbon::parse($date)->startOfDay();

        return $this->bookingsOverlappingDay($date)
            ->get()
            ->map(fn (Booking $b) => [
                'start'  => $b->startsAt()->lt($dayStart) ? '00:00' : $b->startsAt()->format('H:i'),
                'end'    => $b->endsAt()->gte($dayStart->copy()->addDay()) ? '24:00' : $b->endsAt()->format('H:i'),
                'status' => $b->status,
            ])
            ->values();
    }

    public function currentBlockingBooking(): ?Booking
    {
        return $this->bookings()
            ->blocking()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->orderBy('starts_at')
            ->first();
    }

    public function nextBlockingBooking(): ?Booking
    {
        return $this->bookings()
            ->blocking()
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->first();
    }

    public function getCurrentPricePerHour(): float
    {
        return $this->pricing?->price_per_hour ?? 0;
    }
}

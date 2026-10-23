<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AyoReservation extends Model
{
    protected $fillable = [
        'booking_id',
        'field_id',
        'date',
        'start_time',
        'end_time',
        'total_price',
        'order_detail_id',
        'ayo_booking_id',
        'status',
        'attempts',
        'last_error',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

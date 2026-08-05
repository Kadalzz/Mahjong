<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'booking_id',
        'amount',
        'payment_method',
        'gateway_transaction_id',
        'gateway_status',
        'gateway_payload',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'gateway_payload' => 'array',
        'paid_at'         => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

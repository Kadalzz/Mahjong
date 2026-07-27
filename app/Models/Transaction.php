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
        'midtrans_transaction_id',
        'midtrans_status',
        'midtrans_payload',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'midtrans_payload' => 'array',
        'paid_at'          => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

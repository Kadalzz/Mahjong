<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerRoundScore extends Model
{
    protected $fillable = ['player_id', 'points', 'is_winner', 'played_at'];

    protected function casts(): array
    {
        return ['is_winner' => 'boolean', 'played_at' => 'datetime'];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}

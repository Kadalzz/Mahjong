<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePlayer extends Model
{
    protected $fillable = ['game_id', 'player_id', 'seat', 'is_ready', 'last_seen_at', 'left_at'];

    protected function casts(): array
    {
        return ['is_ready' => 'boolean', 'last_seen_at' => 'datetime', 'left_at' => 'datetime'];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function getSeatLabelAttribute(): string
    {
        return 'P' . $this->seat;
    }
}

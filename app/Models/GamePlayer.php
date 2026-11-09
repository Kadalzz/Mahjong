<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePlayer extends Model
{
    public const WINDS = [1 => 'East', 2 => 'South', 3 => 'West', 4 => 'North'];

    protected $fillable = ['game_id', 'player_id', 'seat', 'is_ready', 'seen_round', 'last_seen_at', 'left_at'];

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
        return self::windFor($this->seat);
    }

    public static function windFor(int $seat): string
    {
        return self::WINDS[$seat] ?? 'P' . $seat;
    }
}

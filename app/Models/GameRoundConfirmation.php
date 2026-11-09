<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class GameRoundConfirmation extends Model
{
    protected $fillable = ['game_round_id', 'player_id', 'seen_at', 'agreed_at'];

    protected function casts(): array
    {
        return ['seen_at' => 'datetime', 'agreed_at' => 'datetime'];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(GameRound::class, 'game_round_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function deadline(GameRound $claim): Carbon
    {
        return $this->seen_at
            ? $this->seen_at->copy()->addSeconds(max(1, (int) config('poin.confirm_seconds.seen')))
            : $claim->created_at->copy()->addSeconds(max(1, (int) config('poin.confirm_seconds.unseen')));
    }

    public function isSettled(GameRound $claim): bool
    {
        return $this->agreed_at !== null || now()->gte($this->deadline($claim));
    }

    public function secondsLeft(GameRound $claim): int
    {
        return max(0, (int) ceil(now()->diffInMilliseconds($this->deadline($claim), false) / 1000));
    }
}

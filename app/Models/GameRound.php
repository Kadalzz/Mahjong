<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRound extends Model
{
    protected $fillable = ['game_id', 'number', 'winner_player_id', 'discarder_player_id', 'recorded_by_player_id'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PlayerRoundScore::class);
    }

    public function isFromDeck(): bool
    {
        return $this->discarder_player_id === null;
    }
}

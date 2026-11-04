<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    public const STATUS_LOBBY = 'lobby';
    public const STATUS_PLAYING = 'playing';
    public const STATUS_FINISHED = 'finished';

    public const SEATS = 4;

    protected $fillable = ['mahjong_table_id', 'status', 'current_round', 'undo_floor', 'started_at', 'ended_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(MahjongTable::class, 'mahjong_table_id');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(GamePlayer::class)->whereNull('left_at')->orderBy('seat');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(GameRound::class)->orderBy('number');
    }

    public function lastRound(): ?GameRound
    {
        return GameRound::where('game_id', $this->id)->where('number', $this->current_round - 1)->first();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_LOBBY, self::STATUS_PLAYING]);
    }

    public function isReadyToStart(): bool
    {
        return $this->status === self::STATUS_LOBBY
            && $this->seats->where('is_ready', true)->count() === self::SEATS;
    }
}

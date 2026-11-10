<?php

namespace App\Services\Poin;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\MahjongTable;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class LobbyService
{
    public const STALE_SECONDS = 90;

    public function openGameFor(MahjongTable $table): ?Game
    {
        $game = Game::open()->where('mahjong_table_id', $table->id)->with('seats')->first();

        if ($game?->status === Game::STATUS_LOBBY) {
            $this->removeStaleSeats($game);
            $game->load('seats');
        }

        return $game;
    }

    public function activeSeatFor(Player $player): ?GamePlayer
    {
        return GamePlayer::where('player_id', $player->id)
            ->whereNull('left_at')
            ->whereHas('game', fn ($q) => $q->open())
            ->with('game')
            ->first();
    }

    public function tableStatus(MahjongTable $table, ?Game $game, Player $player): string
    {
        if ($table->status === 'maintenance') {
            return 'unavailable';
        }

        if ($game?->seats->contains('player_id', $player->id)) {
            return 'available';
        }

        if ($game?->status === Game::STATUS_PLAYING) {
            return 'playing';
        }

        return $game && $game->seats->count() >= Game::SEATS ? 'full' : 'available';
    }

    public function join(MahjongTable $table, Player $player): Game|string
    {
        return DB::transaction(function () use ($table, $player) {
            Player::whereKey($player->id)->lockForUpdate()->first();
            $table = MahjongTable::whereKey($table->id)->lockForUpdate()->first();

            if ($table->status === 'maintenance') {
                return 'unavailable';
            }

            $current = $this->activeSeatFor($player);

            if ($current && $current->game->mahjong_table_id === $table->id) {
                return $current->game;
            }

            if ($current?->game->status === Game::STATUS_PLAYING) {
                return $current->game;
            }

            if ($current) {
                $this->leave($current->game, $player);
            }

            $game = $this->openGameFor($table);

            if ($game?->status === Game::STATUS_PLAYING) {
                return 'playing';
            }

            $game ??= Game::create(['mahjong_table_id' => $table->id, 'status' => Game::STATUS_LOBBY]);

            $seat = collect(range(1, Game::SEATS))->diff($game->hasMany(GamePlayer::class)->pluck('seat'))->first();

            if (!$seat) {
                return 'full';
            }

            GamePlayer::create([
                'game_id'      => $game->id,
                'player_id'    => $player->id,
                'seat'         => $seat,
                'is_ready'     => false,
                'last_seen_at' => now(),
            ]);

            return $game;
        });
    }

    public function leave(Game $game, Player $player): void
    {
        DB::transaction(function () use ($game, $player) {
            $game->hasMany(GamePlayer::class)->where('player_id', $player->id)->delete();
            $this->closeIfEmpty($game);
        });
    }

    public function start(Game $game): bool
    {
        return DB::transaction(function () use ($game) {
            $game = Game::whereKey($game->id)->lockForUpdate()->with('seats')->first();

            if (!$game->isReadyToStart()) {
                return false;
            }

            $game->update(['status' => Game::STATUS_PLAYING, 'started_at' => now()]);

            return true;
        });
    }

    public function setReady(Game $game, Player $player, bool $ready): void
    {
        DB::transaction(function () use ($game, $player, $ready) {
            $game = Game::whereKey($game->id)->lockForUpdate()->first();

            if ($game->status !== Game::STATUS_LOBBY) {
                return;
            }

            GamePlayer::where('game_id', $game->id)
                ->where('player_id', $player->id)
                ->update(['is_ready' => $ready, 'last_seen_at' => now()]);
        });
    }

    public function moveSeat(Game $game, Player $player, int $seat): bool
    {
        return DB::transaction(function () use ($game, $player, $seat) {
            $game = Game::whereKey($game->id)->lockForUpdate()->first();

            if ($game->status !== Game::STATUS_LOBBY || $seat < 1 || $seat > Game::SEATS) {
                return false;
            }

            $mine = GamePlayer::where('game_id', $game->id)->where('player_id', $player->id)->first();

            if (!$mine || (int) $mine->seat === $seat) {
                return false;
            }

            $other = GamePlayer::where('game_id', $game->id)->where('seat', $seat)->first();
            $from = $mine->seat;

            if ($other) {
                $mine->update(['seat' => 0]);
                $other->update(['seat' => $from, 'is_ready' => false]);
            }

            return $mine->update(['seat' => $seat, 'is_ready' => false, 'last_seen_at' => now()]);
        });
    }

    public function touch(Game $game, Player $player): void
    {
        GamePlayer::where('game_id', $game->id)->where('player_id', $player->id)->update(['last_seen_at' => now()]);
    }

    public function closeIdleGames(): array
    {
        $closed = [];

        foreach ([Game::STATUS_PLAYING => 'playing', Game::STATUS_LOBBY => 'lobby'] as $status => $key) {
            $cutoff = now()->subMinutes(max(1, (int) config("poin.idle_minutes.{$key}")));

            $closed[$status] = Game::where('status', $status)
                ->where('updated_at', '<', $cutoff)
                ->whereNotExists(fn ($query) => $query->selectRaw('1')
                    ->from('game_players')
                    ->whereColumn('game_players.game_id', 'games.id')
                    ->where('game_players.last_seen_at', '>=', $cutoff))
                ->update(['status' => Game::STATUS_FINISHED, 'ended_at' => now()]);
        }

        return $closed;
    }

    private function removeStaleSeats(Game $game): void
    {
        GamePlayer::where('game_id', $game->id)
            ->where('last_seen_at', '<', now()->subSeconds(self::STALE_SECONDS))
            ->delete();

        $this->closeIfEmpty($game);
    }

    private function closeIfEmpty(Game $game): void
    {
        if ($game->status === Game::STATUS_LOBBY && !GamePlayer::where('game_id', $game->id)->exists()) {
            $game->update(['status' => Game::STATUS_FINISHED, 'ended_at' => now()]);
        }
    }
}

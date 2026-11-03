<?php

namespace App\Services\Poin;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\GameRound;
use App\Models\Player;
use App\Models\PlayerRoundScore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoundService
{
    public function __construct(private PointCalculator $calculator)
    {
    }

    public function record(Game $game, Player $winner, int $round, ?int $discarderSeat): ?GameRound
    {
        return DB::transaction(function () use ($game, $winner, $round, $discarderSeat) {
            $game = Game::whereKey($game->id)->lockForUpdate()->with('seats')->first();

            if ($game->status !== Game::STATUS_PLAYING || $game->seats->count() !== Game::SEATS) {
                return null;
            }

            if ($game->current_round !== $round) {
                return $game->lastRound();
            }

            $discarder = $discarderSeat === null ? null : $game->seats->firstWhere('seat', $discarderSeat)?->player_id;

            if ($discarderSeat !== null && (!$discarder || $discarder === $winner->id)) {
                return null;
            }

            $points = $this->calculator->calculate($game->seats->pluck('player_id')->all(), $winner->id, $discarder);

            $record = GameRound::create([
                'game_id'               => $game->id,
                'number'                => $round,
                'winner_player_id'      => $winner->id,
                'discarder_player_id'   => $discarder,
                'recorded_by_player_id' => $winner->id,
            ]);

            $now = now();

            foreach ($points as $playerId => $value) {
                PlayerRoundScore::create([
                    'player_id'     => $playerId,
                    'game_round_id' => $record->id,
                    'points'        => $value,
                    'is_winner'     => $playerId === $winner->id,
                    'played_at'     => $now,
                ]);
            }

            $game->update(['current_round' => $round + 1]);

            return $record;
        });
    }

    public function undo(Game $game, Player $player): bool
    {
        return DB::transaction(function () use ($game, $player) {
            $game = Game::whereKey($game->id)->lockForUpdate()->first();
            $last = $game->status === Game::STATUS_PLAYING ? $game->lastRound() : null;

            if (!$last || $last->recorded_by_player_id !== $player->id) {
                return false;
            }

            $last->delete();
            $game->update(['current_round' => $last->number]);

            GamePlayer::where('game_id', $game->id)
                ->where('seen_round', '>=', $last->number)
                ->update(['seen_round' => $last->number - 1]);

            return true;
        });
    }

    public function acknowledge(Game $game, Player $player): void
    {
        GamePlayer::where('game_id', $game->id)
            ->where('player_id', $player->id)
            ->update(['seen_round' => max(0, $game->fresh()->current_round - 1), 'last_seen_at' => now()]);
    }

    public function finish(Game $game): void
    {
        Game::whereKey($game->id)
            ->where('status', Game::STATUS_PLAYING)
            ->update(['status' => Game::STATUS_FINISHED, 'ended_at' => now()]);
    }

    public function hasUnseenResult(Game $game, GamePlayer $seat): bool
    {
        return $game->status === Game::STATUS_PLAYING && $seat->seen_round < $game->current_round - 1;
    }

    public function urlFor(Game $game, ?GamePlayer $seat = null): string
    {
        return match ($game->status) {
            Game::STATUS_LOBBY   => route('poin.lobby', $game),
            Game::STATUS_PLAYING => $seat && $this->hasUnseenResult($game, $seat) ? route('poin.round.result', $game) : route('poin.game', $game),
            default              => $game->started_at && $seat ? route('poin.round.summary', $game) : route('poin.games'),
        };
    }

    public function standings(Game $game, ?GameRound $round = null): Collection
    {
        $game->loadMissing('seats.player');

        $totals = PlayerRoundScore::query()
            ->join('game_rounds', 'game_rounds.id', '=', 'player_round_scores.game_round_id')
            ->where('game_rounds.game_id', $game->id)
            ->groupBy('player_round_scores.player_id')
            ->selectRaw('player_round_scores.player_id, SUM(player_round_scores.points) as points, SUM(CASE WHEN player_round_scores.is_winner THEN 1 ELSE 0 END) as wins')
            ->toBase()
            ->get()
            ->keyBy('player_id');

        $deltas = $round ? $round->scores()->pluck('points', 'player_id') : collect();

        return $game->seats
            ->map(fn (GamePlayer $seat) => (object) [
                'seat'      => $seat->seat,
                'label'     => $seat->seat_label,
                'player_id' => $seat->player_id,
                'nickname'  => $seat->player->nickname,
                'avatar'    => $seat->player->avatarUrl(),
                'points'    => (int) ($totals[$seat->player_id]->points ?? 0),
                'wins'      => (int) ($totals[$seat->player_id]->wins ?? 0),
                'delta'     => $deltas[$seat->player_id] ?? null,
            ])
            ->sortBy([['points', 'desc'], ['seat', 'asc']])
            ->values()
            ->map(function ($row, int $index) {
                $row->rank = $index + 1;

                return $row;
            });
    }
}

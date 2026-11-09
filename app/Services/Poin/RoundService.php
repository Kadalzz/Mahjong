<?php

namespace App\Services\Poin;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\GameRound;
use App\Models\GameRoundConfirmation;
use App\Models\Player;
use App\Models\PlayerRoundScore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoundService
{
    public function __construct(private PointCalculator $calculator)
    {
    }

    public function claim(Game $game, Player $winner, int $round, ?int $discarderSeat): ?GameRound
    {
        return DB::transaction(function () use ($game, $winner, $round, $discarderSeat) {
            $game = Game::whereKey($game->id)->lockForUpdate()->with('seats')->first();

            if ($game->status !== Game::STATUS_PLAYING || $game->seats->count() !== Game::SEATS) {
                return null;
            }

            if ($game->current_round !== $round) {
                return $game->lastRound();
            }

            if ($pending = $game->pendingClaim()) {
                return $pending;
            }

            $discarder = $discarderSeat === null ? null : $game->seats->firstWhere('seat', $discarderSeat)?->player_id;

            if ($discarderSeat !== null && (!$discarder || $discarder === $winner->id)) {
                return null;
            }

            $claim = GameRound::create([
                'game_id'               => $game->id,
                'number'                => $round,
                'winner_player_id'      => $winner->id,
                'discarder_player_id'   => $discarder,
                'recorded_by_player_id' => $winner->id,
            ]);

            foreach ($game->seats->where('player_id', '!=', $winner->id) as $seat) {
                $claim->confirmations()->create(['player_id' => $seat->player_id]);
            }

            $game->touch();

            return $claim;
        });
    }

    public function settle(Game $game): ?GameRound
    {
        $claim = $game->pendingClaim();

        if (!$claim || !$this->isSettled($claim)) {
            return $claim;
        }

        return DB::transaction(function () use ($game) {
            $game = Game::whereKey($game->id)->lockForUpdate()->with('seats')->first();
            $claim = $game->pendingClaim();

            if (!$claim || !$this->isSettled($claim)) {
                return $claim;
            }

            $points = $this->calculator->calculate($game->seats->pluck('player_id')->all(), $claim->winner_player_id, $claim->discarder_player_id);
            $now = now();

            foreach ($points as $playerId => $value) {
                PlayerRoundScore::create([
                    'player_id'     => $playerId,
                    'game_round_id' => $claim->id,
                    'points'        => $value,
                    'is_winner'     => $playerId === $claim->winner_player_id,
                    'played_at'     => $now,
                ]);
            }

            $claim->update(['confirmed_at' => $now]);
            $game->update(['current_round' => $claim->number + 1]);

            return null;
        });
    }

    public function markSeen(GameRound $claim, Player $player): void
    {
        $claim->confirmations()->where('player_id', $player->id)->whereNull('seen_at')->update(['seen_at' => now()]);
    }

    public function agree(Game $game, Player $player, int $claimId): void
    {
        $claim = $game->pendingClaim();

        if ($claim?->id === $claimId) {
            $claim->confirmations()->where('player_id', $player->id)->whereNull('agreed_at')->update(['agreed_at' => now()]);
        }

        $this->settle($game);
    }

    public function reject(Game $game, Player $player, int $claimId): bool
    {
        $rejected = DB::transaction(function () use ($game, $player, $claimId) {
            $game = Game::whereKey($game->id)->lockForUpdate()->first();
            $claim = $game->pendingClaim();

            if ($claim?->id !== $claimId) {
                return false;
            }

            $mine = $claim->confirmations()->where('player_id', $player->id)->first();

            if (!$mine || $mine->isSettled($claim)) {
                return false;
            }

            $this->close($claim, $player, 'rejected');

            return true;
        });

        if (!$rejected) {
            $this->settle($game->fresh());
        }

        return $rejected;
    }

    public function withdraw(Game $game, Player $player, int $claimId): bool
    {
        return DB::transaction(function () use ($game, $player, $claimId) {
            $game = Game::whereKey($game->id)->lockForUpdate()->first();
            $claim = $game->pendingClaim();

            if ($claim?->id !== $claimId || $claim->winner_player_id !== $player->id) {
                return false;
            }

            $this->close($claim, $player, 'withdrawn');

            return true;
        });
    }

    public function closedClaim(int $claimId): ?array
    {
        return Cache::get("poin:claim:{$claimId}");
    }

    public function isSettled(GameRound $claim): bool
    {
        $claim->loadMissing('confirmations');

        return $claim->confirmations->every(fn (GameRoundConfirmation $confirmation) => $confirmation->isSettled($claim));
    }

    private function close(GameRound $claim, Player $by, string $reason): void
    {
        Cache::put("poin:claim:{$claim->id}", [
            'reason'    => $reason,
            'winner_id' => $claim->winner_player_id,
            'winner'    => $claim->winner->nickname,
            'by_id'     => $by->id,
            'by'        => $by->nickname,
        ], now()->addMinutes(30));

        $claim->delete();
        Game::whereKey($claim->game_id)->update(['updated_at' => now()]);
    }

    public function canUndo(Game $game, GameRound $round, Player $player): bool
    {
        return $game->status === Game::STATUS_PLAYING
            && $round->recorded_by_player_id === $player->id
            && $round->number > $game->undo_floor
            && $round->number === $game->current_round - 1;
    }

    public function undo(Game $game, Player $player, int $round): bool
    {
        return DB::transaction(function () use ($game, $player, $round) {
            $game = Game::whereKey($game->id)->lockForUpdate()->first();
            $last = $game->status === Game::STATUS_PLAYING ? $game->lastRound() : null;

            if (!$last || $last->number !== $round || $game->pendingClaim() || !$this->canUndo($game, $last, $player)) {
                return false;
            }

            $last->delete();
            $game->update(['current_round' => $last->number, 'undo_floor' => $last->number - 1]);

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
        DB::transaction(function () use ($game) {
            $finished = Game::whereKey($game->id)
                ->where('status', Game::STATUS_PLAYING)
                ->update(['status' => Game::STATUS_FINISHED, 'ended_at' => now()]);

            if ($finished) {
                GameRound::where('game_id', $game->id)->whereNull('confirmed_at')->delete();
            }
        });
    }

    public function hasUnseenResult(Game $game, GamePlayer $seat): bool
    {
        return $game->status === Game::STATUS_PLAYING && $seat->seen_round < $game->current_round - 1;
    }

    public function urlFor(Game $game, ?GamePlayer $seat = null): string
    {
        return match ($game->status) {
            Game::STATUS_LOBBY   => route('poin.lobby', $game),
            Game::STATUS_PLAYING => match (true) {
                $game->pendingClaim() !== null                         => route('poin.round.claim', $game),
                $seat !== null && $this->hasUnseenResult($game, $seat) => route('poin.round.result', $game),
                default                                                => route('poin.game', $game),
            },
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

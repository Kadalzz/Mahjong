<?php

namespace App\Services\Poin;

use App\Models\Game;
use App\Models\GameRound;
use App\Models\Player;
use App\Models\PlayerRoundScore;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeaderboardService
{
    public const PERIOD_YEAR = 'tahun';
    public const PERIOD_MONTH = 'bulan';
    public const PERIOD_WEEK = 'minggu';

    public const PERIODS = [self::PERIOD_YEAR, self::PERIOD_MONTH, self::PERIOD_WEEK];

    public function ranking(string $period = self::PERIOD_YEAR, ?int $limit = 50, ?CarbonInterface $before = null): Collection
    {
        return PlayerRoundScore::query()
            ->join('players', 'players.id', '=', 'player_round_scores.player_id')
            ->where('player_round_scores.played_at', '>=', $this->periodStart($period))
            ->when($before, fn ($query) => $query->where('player_round_scores.played_at', '<', $before))
            ->groupBy('players.id', 'players.nickname', 'players.nickname_key', 'players.avatar_path')
            ->selectRaw('players.id as player_id, players.nickname, players.avatar_path, SUM(points) as points, COUNT(*) as plays, SUM(CASE WHEN is_winner THEN 1 ELSE 0 END) as wins')
            ->orderByDesc('points')
            ->orderByDesc('wins')
            ->orderBy('players.nickname_key')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->toBase()
            ->get()
            ->values()
            ->map(fn ($row, int $index) => (object) [
                'rank'      => $index + 1,
                'player_id' => (int) $row->player_id,
                'nickname'  => $row->nickname,
                'avatar'    => Player::avatarUrlFor($row->avatar_path),
                'points'    => (int) $row->points,
                'plays'     => (int) $row->plays,
                'wins'      => (int) $row->wins,
            ]);
    }

    public function playerRow(Player $player, string $period = self::PERIOD_YEAR, ?Game $since = null): ?object
    {
        $row = $this->ranking($period, null)->firstWhere('player_id', $player->id);

        if (!$row) {
            return null;
        }

        $start = $this->firstRoundAt($player, $period, $since);
        $before = $start ? $this->ranking($period, null, $start)->firstWhere('player_id', $player->id) : null;
        $row->movement = $before ? $before->rank - $row->rank : null;

        return $row;
    }

    public function meta(object $row, bool $withMovement = false): string
    {
        $parts = [];

        if ($withMovement && isset($row->movement)) {
            $parts[] = match (true) {
                $row->movement > 0 => "Naik {$row->movement} posisi",
                $row->movement < 0 => 'Turun ' . abs($row->movement) . ' posisi',
                default            => 'Posisi tetap',
            };
        }

        $parts[] = "{$row->plays} main";
        $parts[] = "{$row->wins} menang";

        return implode(' · ', $parts);
    }

    public function lastUpdatedLabel(string $period = self::PERIOD_YEAR): ?string
    {
        $last = PlayerRoundScore::where('played_at', '>=', $this->periodStart($period))->max('played_at');

        return $last
            ? 'Leaderboard diperbarui ' . str_replace(' yang lalu', ' lalu', Carbon::parse($last)->locale('id')->diffForHumans())
            : null;
    }

    public function periodStart(string $period): CarbonInterface
    {
        return match ($period) {
            self::PERIOD_MONTH => now()->startOfMonth(),
            self::PERIOD_WEEK  => now()->startOfWeek(CarbonInterface::MONDAY),
            default            => now()->startOfYear(),
        };
    }

    private function firstRoundAt(Player $player, string $period, ?Game $since): ?CarbonInterface
    {
        $gameId = $since?->id ?? PlayerRoundScore::query()
            ->join('game_rounds', 'game_rounds.id', '=', 'player_round_scores.game_round_id')
            ->where('player_round_scores.player_id', $player->id)
            ->where('player_round_scores.played_at', '>=', $this->periodStart($period))
            ->orderByDesc('player_round_scores.played_at')
            ->value('game_rounds.game_id');

        if (!$gameId) {
            return null;
        }

        $first = PlayerRoundScore::query()
            ->whereIn('game_round_id', GameRound::where('game_id', $gameId)->select('id'))
            ->where('played_at', '>=', $this->periodStart($period))
            ->min('played_at');

        return $first ? Carbon::parse($first) : null;
    }
}

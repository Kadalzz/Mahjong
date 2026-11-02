<?php

namespace App\Services\Poin;

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

    public function ranking(string $period = self::PERIOD_YEAR, int $limit = 50): Collection
    {
        return PlayerRoundScore::query()
            ->join('players', 'players.id', '=', 'player_round_scores.player_id')
            ->where('player_round_scores.played_at', '>=', $this->periodStart($period))
            ->groupBy('players.id', 'players.nickname', 'players.nickname_key')
            ->selectRaw('players.id as player_id, players.nickname, SUM(points) as points, COUNT(*) as plays, SUM(CASE WHEN is_winner THEN 1 ELSE 0 END) as wins')
            ->orderByDesc('points')
            ->orderByDesc('wins')
            ->orderBy('players.nickname_key')
            ->limit($limit)
            ->toBase()
            ->get()
            ->values()
            ->map(fn ($row, int $index) => (object) [
                'rank'      => $index + 1,
                'player_id' => (int) $row->player_id,
                'nickname'  => $row->nickname,
                'points'    => (int) $row->points,
                'plays'     => (int) $row->plays,
                'wins'      => (int) $row->wins,
            ]);
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
}

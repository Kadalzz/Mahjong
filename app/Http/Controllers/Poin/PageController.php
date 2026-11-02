<?php

namespace App\Http\Controllers\Poin;

use App\Http\Controllers\Controller;
use App\Services\Poin\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function landing()
    {
        return view('poin.landing');
    }

    public function landingContent(LeaderboardService $leaderboard)
    {
        return view('poin.partials.landing', [
            'topThree' => $leaderboard->ranking(LeaderboardService::PERIOD_YEAR, 3),
            'playUrl'  => Auth::guard('player')->check() ? route('poin.games') : route('poin.register'),
        ]);
    }

    public function games()
    {
        return view('poin.games');
    }

    public function gamesContent()
    {
        return view('poin.partials.games');
    }

    public function mcr()
    {
        return view('poin.mcr');
    }

    public function leaderboard(Request $request, LeaderboardService $leaderboard)
    {
        $period = in_array($request->query('periode'), LeaderboardService::PERIODS, true)
            ? $request->query('periode')
            : LeaderboardService::PERIOD_YEAR;

        $ranking = $leaderboard->ranking($period);

        return view('poin.leaderboard', [
            'period'   => $period,
            'kicker'   => match ($period) {
                LeaderboardService::PERIOD_MONTH => 'BULAN INI',
                LeaderboardService::PERIOD_WEEK  => 'MINGGU INI',
                default                          => 'MUSIM ' . now()->year,
            },
            'podium'   => $ranking->take(3),
            'others'   => $ranking->slice(3),
            'updated'  => $leaderboard->lastUpdatedLabel($period),
        ]);
    }
}

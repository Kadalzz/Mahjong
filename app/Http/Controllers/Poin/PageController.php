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

    public function leaderboard(Request $request)
    {
        $period = $this->period($request);

        return view('poin.leaderboard', [
            'period'   => $period,
            'player'   => Auth::guard('player')->user(),
            'kicker'   => match ($period) {
                LeaderboardService::PERIOD_MONTH => 'BULAN INI',
                LeaderboardService::PERIOD_WEEK  => 'MINGGU INI',
                default                          => 'MUSIM ' . now()->year,
            },
        ]);
    }

    public function leaderboardContent(Request $request, LeaderboardService $leaderboard)
    {
        $period = $this->period($request);
        $player = Auth::guard('player')->user();
        $ranking = $leaderboard->ranking($period);
        $mine = $player ? $leaderboard->playerRow($player, $period) : null;

        return view('poin.partials.leaderboard', [
            'player'  => $player,
            'podium'  => $ranking->take(3),
            'others'  => $ranking->slice(3),
            'updated' => $leaderboard->lastUpdatedLabel($period),
            'mine'    => $mine,
            'myMeta'  => $mine ? $leaderboard->meta($mine, true) : null,
        ]);
    }

    public function help()
    {
        $staff = config('app.points_staff_whatsapp');

        return view('poin.help', [
            'back'     => Auth::guard('player')->check() ? route('poin.profile') : route('poin.login'),
            'staffUrl' => $staff ? 'https://wa.me/' . $staff : null,
        ]);
    }

    private function period(Request $request): string
    {
        return in_array($request->query('periode'), LeaderboardService::PERIODS, true)
            ? $request->query('periode')
            : LeaderboardService::PERIOD_YEAR;
    }
}

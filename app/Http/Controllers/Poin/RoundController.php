<?php

namespace App\Http\Controllers\Poin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\GameRound;
use App\Services\Poin\LeaderboardService;
use App\Services\Poin\PointCalculator;
use App\Services\Poin\RoundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoundController extends Controller
{
    public function __construct(private RoundService $rounds, private PointCalculator $calculator)
    {
    }

    public function win(Request $request, Game $game)
    {
        $seat = $this->seatFor($request, $game);
        $url = $this->rounds->urlFor($game, $seat);

        if ($url !== route('poin.game', $game)) {
            return redirect()->to($url);
        }

        $game->load('seats.player');

        return view('poin.win', [
            'game'    => $game,
            'seat'    => $seat,
            'options' => $game->seats->reject(fn (GamePlayer $other) => $other->id === $seat->id)->values(),
        ]);
    }

    public function winStatus(Request $request, Game $game): JsonResponse
    {
        $seat = $this->seatFor($request, $game);
        $round = (int) $request->query('ronde');

        if ($game->status !== Game::STATUS_PLAYING || $game->current_round !== $round || $game->pendingClaim()) {
            return response()->json(['redirect' => $this->rounds->urlFor($game, $seat)]);
        }

        return response()->json([]);
    }

    public function store(Request $request, Game $game): JsonResponse|RedirectResponse
    {
        $seat = $this->seatFor($request, $game);
        $data = $request->validate([
            'ronde' => ['required', 'integer', 'min:1'],
            'dari'  => ['required', 'in:deck,1,2,3,4'],
        ]);

        $discarder = $data['dari'] === 'deck' ? null : (int) $data['dari'];
        abort_if($discarder === $seat->seat, 422);

        $this->rounds->claim($game, $request->user('player'), (int) $data['ronde'], $discarder);

        return $this->respond($request, $this->rounds->urlFor($game->fresh(), $seat->fresh()));
    }

    public function claim(Request $request, Game $game)
    {
        $seat = $this->seatFor($request, $game);
        $claim = $this->rounds->settle($game);

        if (!$claim) {
            return redirect()->to($this->rounds->urlFor($game->fresh(), $seat));
        }

        $this->rounds->markSeen($claim, $request->user('player'));

        return view('poin.claim', ['game' => $game->load('table'), 'claim' => $claim]);
    }

    public function claimStatus(Request $request, Game $game): JsonResponse
    {
        $seat = $this->seatFor($request, $game);
        $claimId = (int) $request->query('klaim');
        $claim = $this->rounds->settle($game);

        if (!$claim || $claim->id !== $claimId) {
            $url = $this->rounds->urlFor($game->fresh(), $seat->fresh());

            if ($this->rounds->closedClaim($claimId) && $url === route('poin.game', $game)) {
                $url = route('poin.game', ['game' => $game, 'klaim' => $claimId]);
            }

            return response()->json(['redirect' => $url]);
        }

        $this->rounds->markSeen($claim, $request->user('player'));
        GamePlayer::whereKey($seat->id)->update(['last_seen_at' => now()]);

        return response()->json(['html' => view('poin.partials.claim', $this->claimData($game, $claim, $seat))->render()]);
    }

    public function agree(Request $request, Game $game): RedirectResponse
    {
        $seat = $this->seatFor($request, $game);
        $data = $request->validate(['klaim' => ['required', 'integer']]);
        $this->rounds->agree($game, $request->user('player'), (int) $data['klaim']);

        return redirect()->to($this->rounds->urlFor($game->fresh(), $seat->fresh()));
    }

    public function reject(Request $request, Game $game): RedirectResponse
    {
        $seat = $this->seatFor($request, $game);
        $data = $request->validate(['klaim' => ['required', 'integer']]);

        if ($this->rounds->reject($game, $request->user('player'), (int) $data['klaim'])) {
            return redirect()->route('poin.game', ['game' => $game, 'klaim' => $data['klaim']]);
        }

        return redirect()->to($this->rounds->urlFor($game->fresh(), $seat->fresh()));
    }

    public function withdraw(Request $request, Game $game): RedirectResponse
    {
        $seat = $this->seatFor($request, $game);
        $data = $request->validate(['klaim' => ['required', 'integer']]);

        if ($this->rounds->withdraw($game, $request->user('player'), (int) $data['klaim'])) {
            return redirect()->route('poin.game', ['game' => $game, 'klaim' => $data['klaim']]);
        }

        return redirect()->to($this->rounds->urlFor($game->fresh(), $seat->fresh()));
    }

    private function claimData(Game $game, GameRound $claim, GamePlayer $seat): array
    {
        $game->loadMissing('seats.player');
        $claim->load('confirmations', 'winner', 'discarder');

        $deltas = $this->calculator->calculate($game->seats->pluck('player_id')->all(), $claim->winner_player_id, $claim->discarder_player_id);
        $mine = $claim->confirmations->firstWhere('player_id', $seat->player_id);

        $others = $game->seats
            ->filter(fn (GamePlayer $other) => $other->player_id !== $claim->winner_player_id)
            ->map(function (GamePlayer $other) use ($claim) {
                $confirmation = $claim->confirmations->firstWhere('player_id', $other->player_id);

                return (object) [
                    'nickname' => $other->player->nickname,
                    'agreed'   => $confirmation?->agreed_at !== null,
                    'seconds'  => $confirmation ? $confirmation->secondsLeft($claim) : 0,
                    'seen'     => $confirmation?->seen_at !== null,
                ];
            })
            ->values();

        return [
            'game'     => $game,
            'claim'    => $claim,
            'seat'     => $seat,
            'isWinner' => $claim->winner_player_id === $seat->player_id,
            'mine'     => $mine,
            'seconds'  => $mine ? $mine->secondsLeft($claim) : 0,
            'delta'    => $deltas[$seat->player_id] ?? 0,
            'chips'    => $game->seats->map(fn (GamePlayer $other) => (object) [
                'label' => $other->player_id === $seat->player_id ? $other->player->nickname . ' · Kamu' : $other->player->nickname,
                'delta' => $deltas[$other->player_id] ?? 0,
            ]),
            'others'   => $others,
            'agreed'   => $others->where('agreed', true)->count(),
            'source'   => $claim->discarder_player_id === null ? 'Deck' : ($claim->discarder_player_id === $seat->player_id ? 'kamu' : $claim->discarder->nickname),
        ];
    }

    public function result(Request $request, Game $game)
    {
        $seat = $this->seatFor($request, $game);
        $round = $game->status === Game::STATUS_PLAYING ? $game->lastRound() : null;

        if (!$round || $game->pendingClaim()) {
            return redirect()->to($this->rounds->urlFor($game, $seat));
        }

        $standings = $this->rounds->standings($game, $round);
        $winner = $standings->firstWhere('player_id', $round->winner_player_id);
        $discarder = $round->discarder_player_id ? $standings->firstWhere('player_id', $round->discarder_player_id) : null;

        return view($round->winner_player_id === $seat->player_id ? 'poin.round-win' : 'poin.round-lose', [
            'game'      => $game,
            'seat'      => $seat,
            'round'     => $round,
            'standings' => $standings,
            'winner'    => $winner,
            'discarder' => $discarder,
            'mine'      => $standings->firstWhere('player_id', $seat->player_id),
            'canUndo'   => $this->rounds->canUndo($game, $round, $request->user('player')),
        ]);
    }

    public function resultStatus(Request $request, Game $game): JsonResponse
    {
        $seat = $this->seatFor($request, $game);
        $round = (int) $request->query('ronde');

        if ($game->status !== Game::STATUS_PLAYING || $game->current_round - 1 !== $round || $game->pendingClaim()) {
            return response()->json(['redirect' => $this->rounds->urlFor($game, $seat)]);
        }

        return response()->json([]);
    }

    public function continue(Request $request, Game $game): JsonResponse|RedirectResponse
    {
        $seat = $this->seatFor($request, $game);
        $this->rounds->acknowledge($game, $request->user('player'));

        return $this->respond($request, $this->rounds->urlFor($game->fresh(), $seat->fresh()));
    }

    public function undo(Request $request, Game $game): RedirectResponse
    {
        $seat = $this->seatFor($request, $game);
        $data = $request->validate(['ronde' => ['required', 'integer', 'min:1']]);
        $this->rounds->undo($game, $request->user('player'), (int) $data['ronde']);

        return redirect()->to($this->rounds->urlFor($game->fresh(), $seat->fresh()));
    }

    public function stop(Request $request, Game $game): JsonResponse|RedirectResponse
    {
        $seat = $this->seatFor($request, $game);
        $this->rounds->finish($game);

        return $this->respond($request, $this->rounds->urlFor($game->fresh(), $seat));
    }

    public function summary(Request $request, Game $game, LeaderboardService $leaderboard)
    {
        $seat = $this->seatFor($request, $game);

        if ($game->status !== Game::STATUS_FINISHED) {
            return redirect()->to($this->rounds->urlFor($game, $seat));
        }

        $player = $request->user('player');
        $mine = $this->rounds->standings($game)->firstWhere('player_id', $player->id);
        $row = $leaderboard->playerRow($player, LeaderboardService::PERIOD_YEAR, $game);

        return view('poin.summary', [
            'mine' => $mine,
            'row'  => $row,
            'meta' => $row ? $leaderboard->meta($row, true) : null,
        ]);
    }

    private function respond(Request $request, string $url): JsonResponse|RedirectResponse
    {
        return $request->expectsJson() ? response()->json(['redirect' => $url]) : redirect()->to($url);
    }

    private function seatFor(Request $request, Game $game): GamePlayer
    {
        $seat = $game->seats()->where('player_id', $request->user('player')->id)->first();
        abort_unless($seat, 403);

        if ($game->status === Game::STATUS_PLAYING) {
            $this->rounds->settle($game);
            $game->refresh();
        }

        return $seat;
    }
}

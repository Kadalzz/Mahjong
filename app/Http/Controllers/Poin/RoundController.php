<?php

namespace App\Http\Controllers\Poin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Services\Poin\LeaderboardService;
use App\Services\Poin\RoundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoundController extends Controller
{
    public function __construct(private RoundService $rounds)
    {
    }

    public function win(Request $request, Game $game)
    {
        $seat = $this->seatFor($request, $game);

        if ($game->status !== Game::STATUS_PLAYING || $this->rounds->hasUnseenResult($game, $seat)) {
            return redirect()->to($this->rounds->urlFor($game, $seat));
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

        if ($game->status !== Game::STATUS_PLAYING || $game->current_round !== $round) {
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

        $this->rounds->record($game, $request->user('player'), (int) $data['ronde'], $discarder);

        return $this->respond($request, $this->rounds->urlFor($game->fresh(), $seat->fresh()));
    }

    public function result(Request $request, Game $game)
    {
        $seat = $this->seatFor($request, $game);
        $round = $game->status === Game::STATUS_PLAYING ? $game->lastRound() : null;

        if (!$round) {
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

        if ($game->status !== Game::STATUS_PLAYING || $game->current_round - 1 !== $round) {
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

        return $seat;
    }
}

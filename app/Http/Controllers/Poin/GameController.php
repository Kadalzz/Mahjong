<?php

namespace App\Http\Controllers\Poin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\MahjongTable;
use App\Services\Poin\LobbyService;
use App\Services\Poin\RoundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function __construct(private LobbyService $lobby, private RoundService $rounds)
    {
    }

    public function scan(Request $request)
    {
        $seat = $this->lobby->activeSeatFor($request->user('player'));

        if ($seat) {
            return redirect()->to($this->rounds->urlFor($seat->game, $seat));
        }

        return view('poin.scan', ['unknown' => $request->query('status') === 'unknown']);
    }

    public function table(string $code)
    {
        if (!$this->findTable($code)) {
            return redirect()->route('poin.scan', ['status' => 'unknown']);
        }

        return view('poin.table', ['code' => strtoupper($code)]);
    }

    public function tableContent(Request $request, string $code)
    {
        $table = $this->findTable($code);
        abort_unless($table, 404);

        $player = $request->user('player');
        $game   = $this->lobby->openGameFor($table);
        $status = $this->lobby->tableStatus($table, $game, $player);
        $seats  = $game?->seats ?? collect();
        $mine   = $seats->firstWhere('player_id', $player->id);
        $free   = collect(range(1, Game::SEATS))->diff($seats->pluck('seat'))->first();

        return view('poin.partials.table', [
            'table'   => $table,
            'status'  => $status,
            'count'   => $seats->count(),
            'youSeat' => 'P' . ($mine?->seat ?? $free ?? 1),
        ]);
    }

    public function join(Request $request, string $code): RedirectResponse
    {
        $table = $this->findTable($code);

        if (!$table) {
            return redirect()->route('poin.scan', ['status' => 'unknown']);
        }

        $player = $request->user('player');
        $result = $this->lobby->join($table, $player);

        return $result instanceof Game
            ? redirect()->to($this->rounds->urlFor($result, $result->seats()->where('player_id', $player->id)->first()))
            : redirect()->route('poin.table', $table->qr_code);
    }

    public function lobby(Request $request, Game $game)
    {
        $seat = $this->seatFor($request, $game);

        if ($game->status !== Game::STATUS_LOBBY) {
            return redirect()->to($this->rounds->urlFor($game, $seat));
        }

        return view('poin.lobby', ['game' => $game]);
    }

    public function lobbyStatus(Request $request, Game $game): JsonResponse
    {
        $seat = $this->seatFor($request, $game);

        if ($game->status !== Game::STATUS_LOBBY) {
            return response()->json(['redirect' => $this->rounds->urlFor($game, $seat)]);
        }

        $player = $request->user('player');
        $this->lobby->touch($game, $player);
        $this->lobby->openGameFor($game->table);
        $game->load('seats.player', 'table');

        return response()->json(['html' => view('poin.partials.lobby', compact('game', 'player'))->render()]);
    }

    public function ready(Request $request, Game $game): RedirectResponse
    {
        $seat = $this->seatFor($request, $game);
        $this->lobby->setReady($game, $request->user('player'), $request->boolean('ready'));

        return redirect()->to($this->rounds->urlFor($game->fresh(), $seat));
    }

    public function start(Request $request, Game $game): RedirectResponse
    {
        $seat = $this->seatFor($request, $game);
        $this->lobby->start($game);

        return redirect()->to($this->rounds->urlFor($game->fresh(), $seat));
    }

    public function leave(Request $request, Game $game): RedirectResponse
    {
        $this->seatFor($request, $game);

        if ($game->status === Game::STATUS_LOBBY) {
            $this->lobby->leave($game, $request->user('player'));
        }

        return redirect()->route('poin.scan');
    }

    public function game(Request $request, Game $game)
    {
        $seat = $this->seatFor($request, $game);

        if ($game->status !== Game::STATUS_PLAYING || $this->rounds->hasUnseenResult($game, $seat)) {
            return redirect()->to($this->rounds->urlFor($game, $seat));
        }

        return view('poin.game', ['game' => $game->load('table')]);
    }

    public function gameStatus(Request $request, Game $game): JsonResponse
    {
        $seat = $this->seatFor($request, $game);

        if ($game->status !== Game::STATUS_PLAYING || $this->rounds->hasUnseenResult($game, $seat)) {
            return response()->json(['redirect' => $this->rounds->urlFor($game, $seat)]);
        }

        $this->lobby->touch($game, $request->user('player'));
        $points = $this->rounds->standings($game)->firstWhere('player_id', $seat->player_id)->points;

        return response()->json(['html' => view('poin.partials.game', compact('game', 'seat', 'points'))->render()]);
    }

    private function findTable(string $code): ?MahjongTable
    {
        return MahjongTable::where('qr_code', strtoupper($code))->first();
    }

    private function seatFor(Request $request, Game $game): GamePlayer
    {
        $seat = $game->seats()->where('player_id', $request->user('player')->id)->first();
        abort_unless($seat, 403);

        return $seat;
    }
}

<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Player;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $players = Player::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . mb_strtolower($search) . '%';
                $query->where(fn ($q) => $q->where('nickname_key', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('nickname_key')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.players.index', compact('players', 'search'));
    }

    public function edit(Player $player)
    {
        return view('dashboard.players.form', compact('player'));
    }

    public function update(Request $request, Player $player)
    {
        $data = $request->validate([
            'password' => Player::PASSWORD_RULES,
        ], [
            'password.min'   => Player::PASSWORD_MESSAGE,
            'password.regex' => Player::PASSWORD_MESSAGE,
        ]);

        $player->update(['password' => $data['password']]);

        ActivityLog::record($request->user(), 'player_password_reset', detail: "{$player->nickname} ({$player->email})");

        return redirect()->route('dashboard.players.index')
            ->with('success', "Password pemain {$player->nickname} berhasil direset.");
    }
}

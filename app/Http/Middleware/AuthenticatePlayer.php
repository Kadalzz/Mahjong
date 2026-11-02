<?php

namespace App\Http\Middleware;

use App\Models\Player;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatePlayer
{
    public function handle(Request $request, Closure $next): mixed
    {
        $guard  = Auth::guard('player');
        $player = $guard->user();

        if ($player && hash_equals($player->passwordFingerprint(), (string) $request->session()->get(Player::SESSION_FINGERPRINT))) {
            return $next($request);
        }

        if ($player) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $request->ajax() || $request->expectsJson()
            ? redirect()->route('poin.login')
            : redirect()->guest(route('poin.login'));
    }
}

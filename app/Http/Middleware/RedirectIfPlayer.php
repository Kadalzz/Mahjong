<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfPlayer
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::guard('player')->check()) {
            return redirect()->route('poin.games');
        }

        return $next($request);
    }
}

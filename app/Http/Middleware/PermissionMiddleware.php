<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): mixed
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->hasAnyPermission(...$permissions)) {
            abort(403, 'Akun Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}

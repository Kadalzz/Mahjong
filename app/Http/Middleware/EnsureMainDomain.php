<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMainDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $domain = strtolower(trim((string) config('app.main_domain')));

        abort_unless($domain === '' || in_array(strtolower($request->getHost()), [$domain, 'www.' . $domain], true), 404);

        return $next($request);
    }
}

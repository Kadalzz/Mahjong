<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        \App\Models\Booking::observe(\App\Observers\BookingObserver::class);

        $trustedProxies = (string) config('app.trusted_proxies');
        if ($trustedProxies !== '') {
            TrustProxies::at($trustedProxies === '*' ? '*' : array_map('trim', explode(',', $trustedProxies)));
        }

        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $visitor = fn (Request $request) => $request->hasSession() && $request->session()->getId()
            ? 'session:' . $request->session()->getId()
            : 'ip:' . $request->ip();

        RateLimiter::for('booking-check', fn (Request $request) => [
            Limit::perMinute(60)->by('check|' . $visitor($request)),
            Limit::perMinute(1200)->by('check-ip|' . $request->ip()),
        ]);

        RateLimiter::for('booking-store', fn (Request $request) => [
            Limit::perMinute(10)->by('store|' . $visitor($request)),
            Limit::perMinute(150)->by('store-ip|' . $request->ip()),
        ]);

        RateLimiter::for('booking-status', fn (Request $request) => [
            Limit::perMinute(40)->by('status|' . $request->route('code') . '|' . $visitor($request)),
            Limit::perMinute(1800)->by('status-ip|' . $request->ip()),
        ]);

        RateLimiter::for('booking-lookup', fn (Request $request) => [
            Limit::perMinute(20)->by('lookup|' . $visitor($request)),
            Limit::perMinute(300)->by('lookup-ip|' . $request->ip()),
        ]);
    }
}

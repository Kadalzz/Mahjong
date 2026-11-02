<?php

use App\Http\Controllers\Poin\AuthController;
use App\Http\Controllers\Poin\PageController;
use App\Http\Middleware\AuthenticatePlayer;
use App\Http\Middleware\RedirectIfPlayer;
use Illuminate\Support\Facades\Route;

$pointsDomain = config('app.points_domain');

if ($pointsDomain) {
    Route::domain($pointsDomain)->name('poin.')->group(function () {
        Route::get('/', [PageController::class, 'landing'])->name('home');
        Route::get('/konten/beranda', [PageController::class, 'landingContent'])->name('home.content');
        Route::get('/leaderboard', [PageController::class, 'leaderboard'])->name('leaderboard');

        Route::middleware(RedirectIfPlayer::class)->group(function () {
            Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
            Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:poin-register');
            Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
            Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:poin-login');
        });

        Route::middleware(AuthenticatePlayer::class)->group(function () {
            Route::get('/pilih-game', [PageController::class, 'games'])->name('games');
            Route::get('/konten/pilih-game', [PageController::class, 'gamesContent'])->name('games.content');
            Route::get('/mcr', [PageController::class, 'mcr'])->name('mcr');
            Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
        });
    });
}

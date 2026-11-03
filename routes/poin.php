<?php

use App\Http\Controllers\Poin\AuthController;
use App\Http\Controllers\Poin\GameController;
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

            Route::get('/scan', [GameController::class, 'scan'])->name('scan');
            Route::get('/meja/{code}', [GameController::class, 'table'])->name('table');
            Route::get('/konten/meja/{code}', [GameController::class, 'tableContent'])->name('table.content');
            Route::post('/meja/{code}/masuk', [GameController::class, 'join'])->name('table.join');

            Route::get('/lobby/{game}', [GameController::class, 'lobby'])->name('lobby');
            Route::get('/lobby/{game}/status', [GameController::class, 'lobbyStatus'])->name('lobby.status');
            Route::post('/lobby/{game}/siap', [GameController::class, 'ready'])->name('lobby.ready');
            Route::post('/lobby/{game}/start', [GameController::class, 'start'])->name('lobby.start');
            Route::post('/lobby/{game}/keluar', [GameController::class, 'leave'])->name('lobby.leave');

            Route::get('/game/{game}', [GameController::class, 'game'])->name('game');
            Route::get('/game/{game}/status', [GameController::class, 'gameStatus'])->name('game.status');
        });
    });
}

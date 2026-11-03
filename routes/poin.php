<?php

use App\Http\Controllers\Poin\AuthController;
use App\Http\Controllers\Poin\GameController;
use App\Http\Controllers\Poin\PageController;
use App\Http\Controllers\Poin\ProfileController;
use App\Http\Controllers\Poin\RoundController;
use App\Http\Middleware\AuthenticatePlayer;
use App\Http\Middleware\RedirectIfPlayer;
use Illuminate\Support\Facades\Route;

$pointsDomain = config('app.points_domain');

if ($pointsDomain) {
    Route::domain($pointsDomain)->name('poin.')->group(function () {
        Route::get('/', [PageController::class, 'landing'])->name('home');
        Route::get('/konten/beranda', [PageController::class, 'landingContent'])->name('home.content');
        Route::get('/leaderboard', [PageController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/konten/leaderboard', [PageController::class, 'leaderboardContent'])->name('leaderboard.content');
        Route::get('/bantuan', [PageController::class, 'help'])->name('help');

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

            Route::get('/profil', [ProfileController::class, 'show'])->name('profile');
            Route::get('/konten/profil', [ProfileController::class, 'content'])->name('profile.content');
            Route::get('/profil/foto', [ProfileController::class, 'photo'])->name('profile.photo');
            Route::post('/profil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
            Route::post('/profil/foto/hapus', [ProfileController::class, 'removePhoto'])->name('profile.photo.remove');
            Route::post('/profil/foto/urungkan', [ProfileController::class, 'restorePhoto'])->name('profile.photo.restore');
            Route::get('/profil/username', [ProfileController::class, 'username'])->name('profile.username');
            Route::post('/profil/username', [ProfileController::class, 'updateUsername']);
            Route::get('/profil/sandi', [ProfileController::class, 'password'])->name('profile.password');
            Route::post('/profil/sandi', [ProfileController::class, 'updatePassword'])->middleware('throttle:10,1');

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

            Route::get('/game/{game}/menang', [RoundController::class, 'win'])->name('round.win');
            Route::get('/game/{game}/menang/status', [RoundController::class, 'winStatus'])->name('round.win.status');
            Route::post('/game/{game}/ronde', [RoundController::class, 'store'])->name('round.store');
            Route::get('/game/{game}/hasil', [RoundController::class, 'result'])->name('round.result');
            Route::get('/game/{game}/hasil/status', [RoundController::class, 'resultStatus'])->name('round.result.status');
            Route::post('/game/{game}/lanjut', [RoundController::class, 'continue'])->name('round.continue');
            Route::post('/game/{game}/batal', [RoundController::class, 'undo'])->name('round.undo');
            Route::post('/game/{game}/berhenti', [RoundController::class, 'stop'])->name('round.stop');
            Route::get('/game/{game}/selesai', [RoundController::class, 'summary'])->name('round.summary');
        });
    });
}

<?php

use App\Http\Controllers\Poin\AuthController;
use App\Http\Controllers\Poin\HomeController;
use App\Http\Middleware\AuthenticatePlayer;
use App\Http\Middleware\RedirectIfPlayer;
use Illuminate\Support\Facades\Route;

$pointsDomain = config('app.points_domain');

if ($pointsDomain) {
    Route::domain($pointsDomain)->name('poin.')->group(function () {
        Route::middleware(RedirectIfPlayer::class)->group(function () {
            Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
            Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:poin-register');
            Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
            Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:poin-login');
        });

        Route::middleware(AuthenticatePlayer::class)->group(function () {
            Route::get('/', HomeController::class)->name('home');
            Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
        });
    });
}

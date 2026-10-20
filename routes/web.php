<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\Dashboard\AccountController;
use App\Http\Controllers\Dashboard\ActivityController;
use App\Http\Controllers\Dashboard\BookingManageController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\OccupancyController;
use App\Http\Controllers\Dashboard\PauseController;
use App\Http\Controllers\Dashboard\PricingController;
use App\Http\Controllers\Dashboard\RevenueController;
use App\Http\Controllers\Dashboard\TableController;
use App\Http\Controllers\CoverController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CoverController::class, 'index'])->name('cover.index');

Route::get('/pesan', [BookingController::class, 'index'])->name('booking.index');
Route::get('/booking/table/{table}', [BookingController::class, 'create'])->name('booking.create');
Route::post('/booking/table/{table}/availability', [BookingController::class, 'checkAvailability'])
    ->middleware('throttle:booking-check')
    ->name('booking.check');
Route::post('/booking', [BookingController::class, 'store'])
    ->middleware('throttle:booking-store')
    ->name('booking.store');
Route::get('/booking/confirm/{code}', [BookingController::class, 'confirm'])->name('booking.confirm');
Route::get('/booking/invoice/{code}', [BookingController::class, 'invoice'])->name('booking.invoice');
Route::get('/booking/status/{code}', [BookingController::class, 'status'])
    ->middleware('throttle:booking-status')
    ->name('booking.status');
Route::get('/cek-booking', [BookingController::class, 'lookup'])
    ->middleware('throttle:booking-lookup')
    ->name('booking.lookup');

Route::get('/jadwal', [ScheduleController::class, 'index'])->name('schedule.index');

Route::post('/doku/webhook', [BookingController::class, 'webhook'])
    ->name('doku.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

require __DIR__ . '/auth.php';

Route::prefix('dashboard')->name('dashboard.')->middleware(['auth', 'active'])->group(function () {
    Route::view('/akses', 'dashboard.noaccess')->name('noaccess');

    Route::get('/', [DashboardController::class, 'index'])->middleware('permission:dashboard')->name('index');

    Route::middleware('permission:revenue')->group(function () {
        Route::get('/revenue', [RevenueController::class, 'index'])->name('revenue');
        Route::get('/occupancy', [OccupancyController::class, 'index'])->name('occupancy');
    });

    Route::middleware('permission:pricing')->group(function () {
        Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
        Route::put('/pricing/{table}', [PricingController::class, 'update'])->name('pricing.update');
    });

    Route::get('/tables', [TableController::class, 'index'])->middleware('permission:tables_manage,tables_power')->name('tables.index');
    Route::post('/tables/{table}/device', [TableController::class, 'device'])->middleware('permission:tables_power')->name('tables.device');
    Route::middleware('permission:tables_manage')->group(function () {
        Route::get('/tables/create', [TableController::class, 'create'])->name('tables.create');
        Route::post('/tables', [TableController::class, 'store'])->name('tables.store');
        Route::get('/tables/{table}/edit', [TableController::class, 'edit'])->name('tables.edit');
        Route::put('/tables/{table}', [TableController::class, 'update'])->name('tables.update');
        Route::delete('/tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');
    });

    Route::get('/bookings', [BookingManageController::class, 'index'])
        ->middleware('permission:bookings_view,bookings_manage,bookings_create')->name('bookings');
    Route::middleware('permission:bookings_create')->group(function () {
        Route::get('/bookings/create', [BookingManageController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [BookingManageController::class, 'store'])->name('bookings.store');
    });
    Route::put('/bookings/{booking}/status', [BookingManageController::class, 'updateStatus'])
        ->middleware('permission:bookings_manage')->name('bookings.status');

    Route::middleware('permission:pause')->group(function () {
        Route::get('/jeda', [PauseController::class, 'index'])->name('jeda');
        Route::post('/jeda/{table}', [PauseController::class, 'start'])->middleware('throttle:30,1')->name('jeda.start');
        Route::post('/jeda/{table}/stop', [PauseController::class, 'stop'])->middleware('throttle:30,1')->name('jeda.stop');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::get('/accounts/{user}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('/accounts/{user}', [AccountController::class, 'update'])->name('accounts.update');
        Route::post('/accounts/{user}/toggle', [AccountController::class, 'toggle'])->name('accounts.toggle');
        Route::get('/activity', [ActivityController::class, 'index'])->name('activity');
    });
});

Route::get('/home', fn () => redirect(auth()->user()->landingUrl()))->middleware(['auth', 'active'])->name('home');

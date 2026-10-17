<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\Dashboard\BookingManageController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\OccupancyController;
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

Route::prefix('dashboard')->name('dashboard.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/revenue', [RevenueController::class, 'index'])->name('revenue');
    Route::get('/occupancy', [OccupancyController::class, 'index'])->name('occupancy');
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
    Route::put('/pricing/{table}', [PricingController::class, 'update'])->name('pricing.update');
    Route::get('/tables', [TableController::class, 'index'])->name('tables.index');
    Route::get('/tables/create', [TableController::class, 'create'])->name('tables.create');
    Route::post('/tables', [TableController::class, 'store'])->name('tables.store');
    Route::get('/tables/{table}/edit', [TableController::class, 'edit'])->name('tables.edit');
    Route::put('/tables/{table}', [TableController::class, 'update'])->name('tables.update');
    Route::delete('/tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');
    Route::post('/tables/{table}/device', [TableController::class, 'device'])->name('tables.device');
    Route::post('/tables/{table}/pause', [TableController::class, 'pause'])->name('tables.pause');
    Route::get('/bookings', [BookingManageController::class, 'index'])->name('bookings');
    Route::get('/bookings/create', [BookingManageController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingManageController::class, 'store'])->name('bookings.store');
    Route::put('/bookings/{booking}/status', [BookingManageController::class, 'updateStatus'])->name('bookings.status');
});

Route::get('/home', function () {
    if (auth()->user()?->isAdmin()) {
        return redirect()->route('dashboard.index');
    }
    return redirect()->route('schedule.index');
})->middleware('auth')->name('home');

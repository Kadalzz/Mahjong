<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\Dashboard\BookingManageController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\OccupancyController;
use App\Http\Controllers\Dashboard\PricingController;
use App\Http\Controllers\Dashboard\RevenueController;
use App\Http\Controllers\Dashboard\TableController;
use App\Http\Controllers\CoverController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cover / Landing Page (Public)
|--------------------------------------------------------------------------
*/
Route::get('/', [CoverController::class, 'index'])->name('cover.index');
Route::get('/beranda', [LandingController::class, 'index'])->name('landing.index');

/*
|--------------------------------------------------------------------------
| User Side (Public)
|--------------------------------------------------------------------------
*/
Route::get('/pesan', [BookingController::class, 'index'])->name('booking.index');
Route::get('/booking/table/{table}', [BookingController::class, 'create'])->name('booking.create');
Route::post('/booking/table/{table}/availability', [BookingController::class, 'checkAvailability'])->name('booking.check');
Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
Route::get('/booking/confirm/{code}', [BookingController::class, 'confirm'])->name('booking.confirm');
Route::get('/booking/invoice/{code}', [BookingController::class, 'invoice'])->name('booking.invoice');
Route::get('/cek-booking', [BookingController::class, 'lookup'])->name('booking.lookup');

// Public schedule (waiting list)
Route::get('/jadwal', [ScheduleController::class, 'index'])->name('schedule.index');

// Xendit invoice callback (webhook)
Route::post('/xendit/webhook', [BookingController::class, 'webhook'])
    ->name('xendit.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

/*
|--------------------------------------------------------------------------
| Auth Routes (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Dashboard (Admin only)
|--------------------------------------------------------------------------
*/
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
    Route::get('/bookings', [BookingManageController::class, 'index'])->name('bookings');
    Route::put('/bookings/{booking}/status', [BookingManageController::class, 'updateStatus'])->name('bookings.status');
});

/*
|--------------------------------------------------------------------------
| After login redirect based on role
|--------------------------------------------------------------------------
*/
Route::get('/home', function () {
    if (auth()->user()?->isAdmin()) {
        return redirect()->route('dashboard.index');
    }
    return redirect()->route('schedule.index');
})->middleware('auth')->name('home');

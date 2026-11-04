<?php

use App\Http\Middleware\EnsureMainDomain;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/poin.php';

Route::middleware(EnsureMainDomain::class)->group(__DIR__ . '/main.php');

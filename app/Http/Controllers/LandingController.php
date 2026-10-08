<?php

namespace App\Http\Controllers;

use App\Models\MahjongTable;

class LandingController extends Controller
{
    public function index()
    {
        $tables = MahjongTable::with('pricing')
            ->where('status', '!=', 'maintenance')
            ->get();

        $startingPrice = $tables->map(fn ($table) => $table->getCurrentPricePerHour())->filter()->sort()->first();

        return view('landing.index', compact('tables', 'startingPrice'));
    }
}

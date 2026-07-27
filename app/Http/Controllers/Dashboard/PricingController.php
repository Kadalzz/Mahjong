<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MahjongTable;
use App\Models\Pricing;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index()
    {
        $tables = MahjongTable::with('pricing')->get();
        return view('dashboard.pricing', compact('tables'));
    }

    public function update(Request $request, MahjongTable $table)
    {
        $request->validate([
            'price_per_hour' => 'required|numeric|min:0',
        ]);

        // Deactivate old pricing
        $table->allPricing()->update(['is_active' => false]);

        // Create new active pricing
        Pricing::create([
            'mahjong_table_id' => $table->id,
            'price_per_hour'   => $request->price_per_hour,
            'is_active'        => true,
        ]);

        return back()->with('success', "Harga {$table->name} berhasil diperbarui.");
    }
}

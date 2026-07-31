<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MahjongTable;
use App\Models\Pricing;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        $tables = MahjongTable::with('pricing')->withCount('bookings')->orderBy('name')->get();

        return view('dashboard.tables.index', compact('tables'));
    }

    public function create()
    {
        return view('dashboard.tables.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'capacity'       => 'required|integer|min:1|max:20',
            'status'         => 'required|in:available,occupied,maintenance',
            'description'    => 'nullable|string|max:500',
            'price_per_hour' => 'required|numeric|min:0',
        ]);

        $table = MahjongTable::create([
            'name'        => $validated['name'],
            'capacity'    => $validated['capacity'],
            'status'      => $validated['status'],
            'description' => $validated['description'] ?? null,
        ]);

        Pricing::create([
            'mahjong_table_id' => $table->id,
            'price_per_hour'   => $validated['price_per_hour'],
            'is_active'        => true,
        ]);

        return redirect()->route('dashboard.tables.index')
            ->with('success', "Meja \"{$table->name}\" berhasil ditambahkan.");
    }

    public function edit(MahjongTable $table)
    {
        return view('dashboard.tables.edit', compact('table'));
    }

    public function update(Request $request, MahjongTable $table)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'capacity'    => 'required|integer|min:1|max:20',
            'status'      => 'required|in:available,occupied,maintenance',
            'description' => 'nullable|string|max:500',
        ]);

        $table->update($validated);

        return redirect()->route('dashboard.tables.index')
            ->with('success', "Meja \"{$table->name}\" berhasil diperbarui.");
    }

    public function destroy(MahjongTable $table)
    {
        if ($table->bookings()->exists()) {
            return back()->with('error',
                "Meja \"{$table->name}\" tidak bisa dihapus karena sudah punya riwayat booking. Ubah status ke Maintenance jika ingin menonaktifkannya.");
        }

        $name = $table->name;
        $table->delete();

        return redirect()->route('dashboard.tables.index')
            ->with('success', "Meja \"{$name}\" berhasil dihapus.");
    }
}

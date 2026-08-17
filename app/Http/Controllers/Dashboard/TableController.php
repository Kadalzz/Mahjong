<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MahjongTable;
use App\Models\Pricing;
use App\Services\TableDeviceService;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function __construct(
        private TableDeviceService $device,
    ) {
    }

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
            'esp32_meja_id'  => 'nullable|integer|min:1|max:255|unique:mahjong_tables,esp32_meja_id',
            'price_per_hour' => 'required|numeric|min:0',
        ]);

        $table = MahjongTable::create([
            'name'          => $validated['name'],
            'capacity'      => $validated['capacity'],
            'status'        => $validated['status'],
            'description'   => $validated['description'] ?? null,
            'esp32_meja_id' => $validated['esp32_meja_id'] ?? null,
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
            'name'          => 'required|string|max:100',
            'capacity'      => 'required|integer|min:1|max:20',
            'status'        => 'required|in:available,occupied,maintenance',
            'description'   => 'nullable|string|max:500',
            'esp32_meja_id' => 'nullable|integer|min:1|max:255|unique:mahjong_tables,esp32_meja_id,' . $table->id,
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

    /**
     * Manual ESP32 on/off override - fallback when the automatic signal
     * (payment webhook, admin status change) fails to reach the Master.
     */
    public function device(Request $request, MahjongTable $table)
    {
        $request->validate(['action' => 'required|in:on,off']);

        if (empty($table->esp32_meja_id)) {
            return back()->with('error', "Meja \"{$table->name}\" belum punya ID Meja ESP32.");
        }

        $sent = $request->action === 'on'
            ? $this->device->turnOn($table)
            : $this->device->turnOff($table);

        if (!$sent) {
            return back()->with('error', "Gagal mengirim perintah ke Meja \"{$table->name}\". Cek koneksi bridge/ESP32 Master.");
        }

        $label = $request->action === 'on' ? 'dinyalakan' : 'dimatikan';
        return back()->with('success', "Meja \"{$table->name}\" berhasil {$label}.");
    }
}

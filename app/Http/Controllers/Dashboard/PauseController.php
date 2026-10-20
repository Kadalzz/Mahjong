<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MahjongTable;
use App\Services\TablePauseService;
use Illuminate\Http\Request;

class PauseController extends Controller
{
    public function __construct(private TablePauseService $pauses)
    {
    }

    public function index()
    {
        $states = MahjongTable::orderBy('name')->get()
            ->map(fn (MahjongTable $table) => $this->pauses->stateFor($table));

        return view('dashboard.jeda', [
            'states'  => $states,
            'minutes' => TablePauseService::MINUTES,
        ]);
    }

    public function start(Request $request, MahjongTable $table)
    {
        $data = $request->validate(['minutes' => 'required|integer|in:' . implode(',', TablePauseService::MINUTES)]);

        $result = $this->pauses->start($table, (int) $data['minutes'], $request->user());

        if (is_string($result)) {
            return back()->with('error', "{$table->name}: {$result}");
        }

        return back()->with('success', "{$table->name} menyala {$data['minutes']} menit dan tertutup untuk pelanggan.");
    }

    public function stop(Request $request, MahjongTable $table)
    {
        $result = $this->pauses->stop($table, $request->user());

        if (is_string($result)) {
            return back()->with('error', "{$table->name}: {$result}");
        }

        return back()->with('success', "{$table->name} dimatikan dan terbuka kembali untuk pelanggan.");
    }
}

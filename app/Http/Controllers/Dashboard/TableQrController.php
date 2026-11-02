<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MahjongTable;

class TableQrController extends Controller
{
    public function __invoke()
    {
        $tables = MahjongTable::orderBy('id')->get();
        $domain = config('app.points_domain');

        return view('dashboard.tables.qr', compact('tables', 'domain'));
    }
}

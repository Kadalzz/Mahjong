<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->get('view', 'harian') === 'bulanan' ? 'bulanan' : 'harian';

        if ($view === 'bulanan') {
            return $this->monthly($request);
        }

        return $this->daily($request);
    }

    private function daily(Request $request)
    {
        $from = $request->get('from', today()->startOfMonth()->toDateString());
        $to   = $request->get('to', today()->toDateString());

        $transactions = Transaction::with('booking.table')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->latest('paid_at')
            ->get();

        $totalRevenue = $transactions->sum('amount');

        // Group by date for chart
        $chartData = $transactions->groupBy(function ($t) {
            return $t->paid_at->format('d/m/Y');
        })->map(fn($group) => $group->sum('amount'));

        return view('dashboard.revenue', [
            'view'         => 'harian',
            'transactions' => $transactions,
            'totalRevenue' => $totalRevenue,
            'from'         => $from,
            'to'           => $to,
            'chartData'    => $chartData,
        ]);
    }

    private function monthly(Request $request)
    {
        $year = (int) $request->get('year', today()->year);

        $transactions = Transaction::with('booking.table')
            ->whereYear('paid_at', $year)
            ->get();

        $totalRevenue = $transactions->sum('amount');

        $byMonth = $transactions->groupBy(fn ($t) => $t->paid_at->format('m'));

        $monthlyData = collect(range(1, 12))->map(function ($m) use ($byMonth) {
            $key   = str_pad($m, 2, '0', STR_PAD_LEFT);
            $group = $byMonth->get($key, collect());

            return [
                'month'   => \Carbon\Carbon::create()->month($m)->translatedFormat('M'),
                'total'   => $group->sum('amount'),
                'count'   => $group->count(),
            ];
        });

        $years = Transaction::whereNotNull('paid_at')
            ->pluck('paid_at')
            ->map(fn ($d) => $d->year)
            ->unique()
            ->sortDesc()
            ->values();

        if ($years->isEmpty() || !$years->contains($year)) {
            $years = $years->push($year)->sortDesc()->values();
        }

        return view('dashboard.revenue', [
            'view'         => 'bulanan',
            'year'         => $year,
            'years'        => $years,
            'monthlyData'  => $monthlyData,
            'totalRevenue' => $totalRevenue,
            'transactions' => $transactions->sortByDesc('paid_at')->values(),
        ]);
    }
}

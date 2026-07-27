<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function index(Request $request)
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

        return view('dashboard.revenue', compact('transactions', 'totalRevenue', 'from', 'to', 'chartData'));
    }
}

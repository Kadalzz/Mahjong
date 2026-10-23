<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MahjongTable;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today()->toDateString();

        
        $revenueToday = Transaction::whereDate('paid_at', today())->sum('amount');

        
        $revenueMonth = Transaction::whereMonth('paid_at', today()->month)
            ->whereYear('paid_at', today()->year)
            ->sum('amount');

        
        $activeBookings = Booking::whereDate('booking_date', $today)
            ->where('status', 'active')
            ->whereNotIn('source', ['block', 'staff'])
            ->count();

        
        $awaitingPaymentCount = Booking::whereDate('booking_date', $today)
            ->where('status', 'pending_payment')
            ->where('created_at', '>', now()->subMinutes(Booking::holdMinutes()))
            ->count();

        
        $todayBookings = Booking::whereDate('booking_date', $today)
            ->whereIn('status', ['active', 'done', 'pending_payment'])
            ->whereNotIn('source', ['block', 'staff'])
            ->count();

        
        $totalTables    = MahjongTable::where('status', '!=', 'maintenance')->count();
        $occupiedTables = Booking::whereDate('booking_date', $today)
            ->where('status', 'active')
            ->whereNotIn('source', ['block', 'staff'])
            ->distinct('mahjong_table_id')
            ->count('mahjong_table_id');

        $occupancyRate = $totalTables > 0 ? round(($occupiedTables / $totalTables) * 100) : 0;

        
        $recentBookings = Booking::with('table')
            ->whereNotIn('source', ['block', 'staff'])
            ->latest()
            ->take(10)
            ->get();

        
        $last7Days = collect(range(6, 0))->map(function ($daysAgo) {
            $date    = today()->subDays($daysAgo);
            $revenue = Transaction::whereDate('paid_at', $date)->sum('amount');
            return [
                'date'    => $date->format('d/m'),
                'revenue' => (float) $revenue,
            ];
        });

        $ayoIssues = Booking::ayoIssues()->count();

        return view('dashboard.index', compact(
            'ayoIssues',
            'revenueToday',
            'revenueMonth',
            'activeBookings',
            'awaitingPaymentCount',
            'todayBookings',
            'occupancyRate',
            'recentBookings',
            'last7Days'
        ));
    }
}

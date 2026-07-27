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

        // Revenue today (from paid transactions)
        $revenueToday = Transaction::whereDate('paid_at', today())->sum('amount');

        // Revenue this month
        $revenueMonth = Transaction::whereMonth('paid_at', today()->month)
            ->whereYear('paid_at', today()->year)
            ->sum('amount');

        // Active bookings right now
        $activeBookings = Booking::whereDate('booking_date', $today)
            ->where('status', 'active')
            ->count();

        // Waiting list count
        $waitingCount = Booking::whereDate('booking_date', $today)
            ->where('status', 'waiting')
            ->count();

        // Today's total bookings
        $todayBookings = Booking::whereDate('booking_date', $today)
            ->whereIn('status', ['active', 'done', 'pending_payment'])
            ->count();

        // Occupancy rate: occupied tables / total tables
        $totalTables    = MahjongTable::where('status', '!=', 'maintenance')->count();
        $occupiedTables = Booking::whereDate('booking_date', $today)
            ->where('status', 'active')
            ->distinct('mahjong_table_id')
            ->count('mahjong_table_id');

        $occupancyRate = $totalTables > 0 ? round(($occupiedTables / $totalTables) * 100) : 0;

        // Recent bookings
        $recentBookings = Booking::with('table')
            ->latest()
            ->take(10)
            ->get();

        // Revenue last 7 days for chart
        $last7Days = collect(range(6, 0))->map(function ($daysAgo) {
            $date    = today()->subDays($daysAgo);
            $revenue = Transaction::whereDate('paid_at', $date)->sum('amount');
            return [
                'date'    => $date->format('d/m'),
                'revenue' => (float) $revenue,
            ];
        });

        return view('dashboard.index', compact(
            'revenueToday',
            'revenueMonth',
            'activeBookings',
            'waitingCount',
            'todayBookings',
            'occupancyRate',
            'recentBookings',
            'last7Days'
        ));
    }
}

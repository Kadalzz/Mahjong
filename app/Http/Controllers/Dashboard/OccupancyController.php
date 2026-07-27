<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MahjongTable;
use Illuminate\Http\Request;

class OccupancyController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('from', today()->subDays(6)->toDateString());
        $to   = $request->get('to', today()->toDateString());

        $tables = MahjongTable::all();

        // Occupancy per table per day
        $occupancyData = [];
        $dates         = [];

        $current = \Carbon\Carbon::parse($from);
        $end     = \Carbon\Carbon::parse($to);

        while ($current->lte($end)) {
            $date    = $current->toDateString();
            $dates[] = $current->format('d/m');

            foreach ($tables as $table) {
                $hoursOccupied = Booking::where('mahjong_table_id', $table->id)
                    ->whereDate('booking_date', $date)
                    ->whereIn('status', ['active', 'done'])
                    ->sum('duration_hours');

                $occupancyData[$table->name][] = (int) $hoursOccupied;
            }

            $current->addDay();
        }

        // Summary: total bookings per table
        $tableSummary = $tables->map(function ($table) use ($from, $to) {
            return [
                'name'           => $table->name,
                'total_bookings' => Booking::where('mahjong_table_id', $table->id)
                    ->whereDate('booking_date', '>=', $from)
                    ->whereDate('booking_date', '<=', $to)
                    ->whereIn('status', ['active', 'done'])
                    ->count(),
                'total_hours'    => Booking::where('mahjong_table_id', $table->id)
                    ->whereDate('booking_date', '>=', $from)
                    ->whereDate('booking_date', '<=', $to)
                    ->whereIn('status', ['active', 'done'])
                    ->sum('duration_hours'),
            ];
        });

        return view('dashboard.occupancy', compact('tables', 'dates', 'occupancyData', 'tableSummary', 'from', 'to'));
    }
}

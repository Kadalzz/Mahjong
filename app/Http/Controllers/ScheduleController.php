<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MahjongTable;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $date   = $request->get('date', today()->toDateString());
        $tables = MahjongTable::with([
            'bookings' => function ($q) use ($date) {
                $q->whereDate('booking_date', $date)
                  ->whereIn('status', ['pending_payment', 'active', 'waiting'])
                  ->orderBy('start_time');
            }
        ])->get();

        // Generate time slots 08:00 - 24:00
        $timeSlots = [];
        for ($h = 8; $h < 24; $h++) {
            $timeSlots[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
        }

        return view('schedule.index', compact('tables', 'date', 'timeSlots'));
    }
}

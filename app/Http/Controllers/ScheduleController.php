<?php

namespace App\Http\Controllers;

use App\Models\MahjongTable;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $date     = $request->get('date', today()->toDateString());
        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEnd   = $dayStart->copy()->addDay();

        $tables = MahjongTable::with([
            'bookings' => function ($q) use ($dayStart, $dayEnd) {
                $q->blocking()
                  ->where('starts_at', '<', $dayEnd)
                  ->where('ends_at', '>', $dayStart)
                  ->orderBy('starts_at');
            }
        ])->get();

        $timeSlots = [];
        for ($h = 0; $h < 24; $h++) {
            $timeSlots[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
        }

        return view('schedule.index', compact('tables', 'date', 'timeSlots', 'dayStart', 'dayEnd'));
    }
}

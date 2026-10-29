<?php

namespace App\Http\Controllers;

use App\Models\MahjongTable;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $date = (string) $request->get('date', today()->toDateString());
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = today()->toDateString();
        }

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

        $openFrom = 0;
        if ($dayStart->isToday()) {
            $openFrom = (int) ceil($dayStart->diffInSeconds(now()) / 60);
        } elseif ($dayStart->lt(today())) {
            $openFrom = 1440;
        }

        $grid = [];
        foreach ($tables as $table) {
            $grid[$table->id] = $this->layoutTable($table, $dayStart, $dayEnd, $openFrom, $date);
        }

        return view('schedule.index', compact('tables', 'date', 'timeSlots', 'grid', 'openFrom'));
    }

    private function layoutTable(MahjongTable $table, Carbon $dayStart, Carbon $dayEnd, int $openFrom, string $date): array
    {
        $cells = array_fill(0, 24, []);
        $busy  = [];

        foreach ($table->bookings as $booking) {
            $from = (int) $dayStart->diffInMinutes($booking->startsAt()->max($dayStart));
            $to   = (int) $dayStart->diffInMinutes($booking->endsAt()->min($dayEnd));

            if ($to <= $from) {
                continue;
            }

            $closed = in_array($booking->source, ['block', 'staff'], true);
            $cells[intdiv($from, 60)][] = [
                'type'   => $closed ? 'blocked' : $booking->status,
                'from'   => $from,
                'to'     => $to,
                'label'  => $this->clock($booking->startsAt(), $dayStart, $from) . '–' . $this->clock($booking->endsAt(), $dayStart, $to),
                'title'  => $closed ? 'Tidak Tersedia' : 'Sudah Dipesan',
                'closed' => $closed,
            ];
            $busy[] = [$from, $to];
        }

        usort($busy, fn ($a, $b) => $a[0] <=> $b[0]);

        $gaps   = [];
        $cursor = $openFrom;
        foreach ($busy as [$from, $to]) {
            if ($from > $cursor) {
                $gaps[] = [$cursor, $from];
            }
            $cursor = max($cursor, $to);
        }
        if ($cursor < 1440) {
            $gaps[] = [$cursor, 1440];
        }

        $bookable = $table->status !== 'maintenance';
        $full     = array_fill(0, 24, false);

        foreach ($gaps as [$from, $to]) {
            $hour = intdiv($from, 60);
            while ($from < $to) {
                $hourEnd = ($hour + 1) * 60;
                $pieceTo = min($to, $hourEnd);

                if ($from === $hour * 60 && $pieceTo === $hourEnd) {
                    $full[$hour] = true;
                } else {
                    $cells[$hour][] = [
                        'type'  => 'free',
                        'from'  => $from,
                        'to'    => $pieceTo,
                        'label' => $this->freeLabel($from, $pieceTo, $hour),
                        'short' => $this->freeLabel($from, $pieceTo, $hour, true),
                        'url'   => $bookable ? route('booking.create', ['table' => $table->id, 'tanggal' => $date, 'jam' => $this->hm($from)]) : null,
                    ];
                }

                $from = $pieceTo;
                $hour++;
            }
        }

        $rows = [];
        for ($h = 0; $h < 24; $h++) {
            $rows[$h] = [
                'items' => $cells[$h],
                'free'  => $full[$h],
                'past'  => ($h + 1) * 60 <= $openFrom,
                'url'   => $full[$h] && $bookable ? route('booking.create', ['table' => $table->id, 'tanggal' => $date, 'jam' => $this->hm($h * 60)]) : null,
            ];
        }

        return $rows;
    }

    private function freeLabel(int $from, int $to, int $hour, bool $short = false): string
    {
        if ($to === ($hour + 1) * 60) {
            return ($short ? 'Kosong ' : 'Kosong mulai ') . $this->hm($from);
        }

        if ($from === $hour * 60) {
            return ($short ? 'Kosong–' : 'Kosong s/d ') . $this->hm($to);
        }

        return ($short ? '' : 'Kosong ') . $this->hm($from) . '–' . $this->hm($to);
    }

    private function clock(Carbon $moment, Carbon $dayStart, int $clamped): string
    {
        return $moment->isSameDay($dayStart) ? $moment->format('H:i') : $this->hm($clamped);
    }

    private function hm(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}

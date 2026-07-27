@extends('layouts.app')

@section('title', 'Jadwal & Waiting List')

@section('head')
<style>
.schedule-hero {
    background: linear-gradient(135deg, #1a1035, #0f0f1a);
    padding: 2.5rem 0;
    border-bottom: 1px solid #2a2a45;
}
.date-nav {
    background: #1a1a2e;
    border: 1px solid #2a2a45;
    border-radius: 14px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.date-nav input[type=date] {
    background: #0f0f1a;
    border: 1px solid #2a2a45;
    color: #e0e0e0;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-size: 0.9rem;
}
.table-grid {
    display: grid;
    grid-template-columns: 80px repeat({{ $tables->count() }}, 1fr);
    border: 1px solid #2a2a45;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 2rem;
    background: #1a1a2e;
}
.grid-header {
    background: #16163a;
    padding: 0.75rem 0.5rem;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #2a2a45;
}
.grid-header.table-name { color: var(--gold); font-size: 0.7rem; }
.grid-time {
    padding: 0.5rem;
    text-align: center;
    font-size: 0.75rem;
    color: #555;
    border-right: 1px solid #2a2a4530;
    border-bottom: 1px solid #2a2a4520;
    background: #13132a;
    font-variant-numeric: tabular-nums;
}
.grid-cell {
    border-right: 1px solid #2a2a4520;
    border-bottom: 1px solid #2a2a4520;
    min-height: 44px;
    position: relative;
}
.grid-cell:last-child { border-right: none; }
.booking-block {
    position: absolute;
    inset: 2px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: 600;
    text-align: center;
    line-height: 1.3;
    padding: 2px;
    cursor: default;
}
.block-active          { background: #16a08530; color: #1abc9c; border: 1px solid #16a08550; }
.block-waiting         { background: #2980b930; color: #3498db; border: 1px solid #2980b950; }
.block-pending_payment { background: #f39c1230; color: #f1c40f; border: 1px solid #f39c1250; }

.waiting-list-card {
    background: #1a1a2e;
    border: 1px solid #2a2a45;
    border-radius: 16px;
    overflow: hidden;
}
.waiting-list-card .card-header {
    background: #16163a;
    border-bottom: 1px solid #2a2a45;
    padding: 1rem 1.5rem;
    font-weight: 600;
    color: #e0e0e0;
}
.booking-row {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #2a2a4530;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.booking-row:last-child { border-bottom: none; }
</style>
@endsection

@section('content')
<div class="schedule-hero">
    <div class="container">
        <h2 class="mb-1">Jadwal & <span class="text-gold">Waiting List</span></h2>
        <p class="text-muted mb-0">Pantau ketersediaan meja secara real-time</p>
    </div>
</div>

<div class="container py-4">

    <!-- Date Navigation -->
    <div class="date-nav">
        <i class="bi bi-calendar3 text-gold"></i>
        <span style="color:#888;font-size:0.9rem">Tanggal:</span>
        <input type="date" id="dateFilter" value="{{ $date }}"
            min="{{ today()->toDateString() }}">
        <button onclick="changeDate()" class="btn btn-gold btn-sm">
            <i class="bi bi-search me-1"></i>Lihat
        </button>
        <div class="ms-auto d-flex gap-2">
            @php
                $dates = collect(range(0, 4))->map(fn($i) => today()->addDays($i));
            @endphp
            @foreach($dates as $d)
            <a href="{{ route('schedule.index', ['date' => $d->toDateString()]) }}"
               class="btn btn-sm {{ $d->toDateString() === $date ? 'btn-gold' : 'btn-outline-secondary' }}">
                {{ $d->format('d/m') }}
            </a>
            @endforeach
        </div>
    </div>

    <!-- Timeline Grid -->
    <div style="overflow-x:auto">
        <div class="table-grid" style="min-width:600px">
            <!-- Header row -->
            <div class="grid-header">Waktu</div>
            @foreach($tables as $table)
            <div class="grid-header table-name">{{ $table->name }}</div>
            @endforeach

            <!-- Time rows -->
            @foreach($timeSlots as $slot)
            <div class="grid-time">{{ $slot }}</div>
            @foreach($tables as $table)
            <div class="grid-cell">
                @foreach($table->bookings as $booking)
                    @php
                        $bStart = substr($booking->start_time, 0, 5);
                        $bEnd   = substr($booking->end_time, 0, 5);
                        $slotHour = (int) substr($slot, 0, 2);
                        $startHour = (int) substr($bStart, 0, 2);
                        if ($slotHour === $startHour):
                    @endphp
                        <div class="booking-block block-{{ $booking->status }}"
                             style="height: calc({{ $booking->duration_hours * 44 }}px - 4px)"
                             title="{{ $booking->customer_name }} | {{ $bStart }}-{{ $bEnd }}">
                            {{ $booking->status === 'waiting' ? '⏳' : '🀄' }}
                            <br>{{ $booking->duration_hours }}j
                        </div>
                    @php endif; @endphp
                @endforeach
            </div>
            @endforeach
            @endforeach
        </div>
    </div>

    <!-- Legend -->
    <div class="d-flex gap-3 mb-4 flex-wrap" style="font-size:0.8rem">
        <span><span class="badge py-1 px-2 me-1" style="background:#16a08530;color:#1abc9c">■</span>Aktif / Sudah Bayar</span>
        <span><span class="badge py-1 px-2 me-1" style="background:#f39c1230;color:#f1c40f">■</span>Menunggu Pembayaran</span>
        <span><span class="badge py-1 px-2 me-1" style="background:#2980b930;color:#3498db">■</span>Waiting List</span>
    </div>

    <!-- Waiting List per table -->
    <h5 class="mb-3 text-gold"><i class="bi bi-clock me-2"></i>Waiting List Hari Ini</h5>
    <div class="row g-3">
        @foreach($tables as $table)
        @php $waiting = $table->bookings->where('status', 'waiting'); @endphp
        @if($waiting->isNotEmpty())
        <div class="col-md-6">
            <div class="waiting-list-card">
                <div class="card-header">
                    <i class="bi bi-clock me-2 text-gold"></i>{{ $table->name }}
                    <span class="badge ms-2 rounded-pill" style="background:#2980b930;color:#3498db">{{ $waiting->count() }} antrian</span>
                </div>
                @foreach($waiting as $i => $bk)
                <div class="booking-row">
                    <div style="width:28px;height:28px;background:#2980b920;color:#3498db;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0">
                        {{ $i + 1 }}
                    </div>
                    <div class="flex-grow-1">
                        <div style="font-weight:600;font-size:0.9rem">{{ $bk->customer_name }}</div>
                        <div style="font-size:0.78rem;color:#888">
                            {{ substr($bk->start_time, 0, 5) }} – {{ substr($bk->end_time, 0, 5) }}
                            &bull; {{ $bk->duration_hours }} jam
                        </div>
                    </div>
                    <div style="font-size:0.8rem;color:#555">{{ $bk->booking_code }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endforeach

        @if($tables->every(fn($t) => $t->bookings->where('status','waiting')->isEmpty()))
        <div class="col-12 text-center py-4 text-muted">
            <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>
            Tidak ada waiting list untuk tanggal ini.
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
function changeDate() {
    const d = document.getElementById('dateFilter').value;
    if (d) window.location.href = '{{ route("schedule.index") }}?date=' + d;
}
document.getElementById('dateFilter').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') changeDate();
});
</script>
@endsection

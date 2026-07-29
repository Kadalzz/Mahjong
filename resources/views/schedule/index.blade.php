@extends('layouts.app')

@section('title', 'Jadwal & Waiting List')

@section('head')
<style>
.schedule-hero {
    background: var(--green);
    padding: 2.5rem 0;
    border-bottom: 1px solid var(--border);
}
.schedule-hero h2 { font-weight: 800; text-transform: uppercase; }
.date-nav {
    background: var(--cream);
    color: var(--ink);
    border-radius: 18px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.date-nav input[type=date] {
    background: rgba(58,20,20,0.06);
    border: none;
    color: var(--ink);
    padding: 0.5rem 1rem;
    border-radius: 999px;
    font-size: 0.9rem;
    font-weight: 700;
}
.date-nav .btn-outline-secondary {
    border-radius: 999px;
    border-color: rgba(58,20,20,0.2);
    color: var(--ink-mute);
}
.table-grid {
    display: grid;
    grid-template-columns: 80px repeat({{ $tables->count() }}, 1fr);
    border-radius: 18px;
    overflow: hidden;
    margin-bottom: 2rem;
    background: var(--cream);
}
.grid-header {
    background: rgba(58,20,20,0.06);
    padding: 0.75rem 0.5rem;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--ink-mute);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid rgba(58,20,20,0.08);
}
.grid-header.table-name { color: var(--red); font-size: 0.7rem; }
.grid-time {
    padding: 0.5rem;
    text-align: center;
    font-size: 0.75rem;
    color: var(--ink-mute);
    border-right: 1px solid rgba(58,20,20,0.06);
    border-bottom: 1px solid rgba(58,20,20,0.06);
    background: rgba(58,20,20,0.03);
    font-variant-numeric: tabular-nums;
}
.grid-cell {
    border-right: 1px solid rgba(58,20,20,0.06);
    border-bottom: 1px solid rgba(58,20,20,0.06);
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
    font-weight: 700;
    text-align: center;
    line-height: 1.3;
    padding: 2px;
    cursor: default;
}
.block-active          { background: #DCEFDD; color: #1f7a34; border: 1px solid #2f9e4450; }
.block-waiting         { background: #DCE8F7; color: #2e6fba; border: 1px solid #2e6fba50; }
.block-pending_payment { background: #FBF0C8; color: #8a6d1a; border: 1px solid #d4a01750; }

.waiting-list-card {
    background: var(--cream);
    color: var(--ink);
    border-radius: 18px;
    overflow: hidden;
}
.waiting-list-card .card-header {
    background: rgba(58,20,20,0.06);
    border-bottom: 1px solid rgba(58,20,20,0.08);
    padding: 1rem 1.5rem;
    font-weight: 700;
    color: var(--ink);
    font-family: 'Baloo 2', sans-serif;
}
.booking-row {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(58,20,20,0.06);
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
        <h2 class="mb-1" style="font-family:'Baloo 2',sans-serif;font-weight:800;font-size:2.5rem;text-transform:uppercase;">
            <span style="color:var(--cream);">Jadwal &amp;</span>
            <span style="color:var(--cream);text-decoration:underline;text-decoration-color:var(--red);text-decoration-thickness:3px;">Waiting List</span>
        </h2>
        <p class="mb-0" style="color:rgba(244,241,221,0.7)">Pantau ketersediaan meja secara real-time</p>
    </div>
</div>

<div class="container py-4">

    <!-- Date Navigation -->
    <div class="date-nav">
        <i class="bi bi-calendar3 text-gold"></i>
        <span style="color:var(--ink-mute);font-size:0.9rem;font-weight:700;">Tanggal:</span>
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
    <div class="d-flex gap-3 mb-4 flex-wrap" style="font-size:0.8rem;color:rgba(244,241,221,0.85);font-weight:600;">
        <span><span class="badge py-1 px-2 me-1" style="background:#DCEFDD;color:#1f7a34">■</span>Aktif / Sudah Bayar</span>
        <span><span class="badge py-1 px-2 me-1" style="background:#FBF0C8;color:#8a6d1a">■</span>Menunggu Pembayaran</span>
        <span><span class="badge py-1 px-2 me-1" style="background:#DCE8F7;color:#2e6fba">■</span>Waiting List</span>
    </div>

    <!-- Waiting List per table -->
    <h5 class="mb-3" style="color:var(--cream);text-transform:uppercase;"><i class="bi bi-clock me-2" style="color:var(--red)"></i>Waiting List Hari Ini</h5>
    <div class="row g-3">
        @foreach($tables as $table)
        @php $waiting = $table->bookings->where('status', 'waiting'); @endphp
        @if($waiting->isNotEmpty())
        <div class="col-md-6">
            <div class="waiting-list-card">
                <div class="card-header">
                    <i class="bi bi-clock me-2 text-gold"></i>{{ $table->name }}
                    <span class="badge ms-2 rounded-pill" style="background:#DCE8F7;color:#2e6fba">{{ $waiting->count() }} antrian</span>
                </div>
                @foreach($waiting as $i => $bk)
                <div class="booking-row">
                    <div style="width:28px;height:28px;background:#DCE8F7;color:#2e6fba;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0">
                        {{ $i + 1 }}
                    </div>
                    <div class="flex-grow-1">
                        <div style="font-weight:700;font-size:0.9rem">{{ $bk->customer_name }}</div>
                        <div style="font-size:0.78rem;color:var(--ink-mute)">
                            {{ substr($bk->start_time, 0, 5) }} – {{ substr($bk->end_time, 0, 5) }}
                            &bull; {{ $bk->duration_hours }} jam
                        </div>
                    </div>
                    <div style="font-size:0.8rem;color:var(--ink-mute)">{{ $bk->booking_code }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endforeach

        @if($tables->every(fn($t) => $t->bookings->where('status','waiting')->isEmpty()))
        <div class="col-12 text-center py-4" style="color:rgba(244,241,221,0.6)">
            <i class="bi bi-check-circle fs-2 d-block mb-2" style="color:#2f9e44"></i>
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

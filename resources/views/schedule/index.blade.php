@extends('layouts.app')

@section('title', 'Jadwal Meja')

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
.grid-time { height: 56px; }
.grid-cell {
    border-right: 1px solid rgba(58,20,20,0.06);
    border-bottom: 1px solid rgba(58,20,20,0.06);
    height: 56px;
    position: relative;
}
.grid-cell.cell-past { background: rgba(58,20,20,0.05); }
.cell-free-link {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: 700;
    color: transparent;
    text-decoration: none;
}
.cell-free-link:hover { background: rgba(31,122,52,0.08); color: #1f7a34; }
.booking-block, .free-block {
    position: absolute;
    left: 2px;
    right: 2px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.68rem;
    font-weight: 800;
    text-align: center;
    line-height: 1.15;
    padding: 1px 3px;
    overflow: hidden;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.booking-block { z-index: 2; cursor: default; }
.free-block {
    z-index: 1;
    background: #ffffff;
    color: #1f7a34;
    border: 1.5px dashed #2f9e44;
    text-decoration: none;
}
a.free-block:hover { background: #e9f7ea; color: #14592a; }
.label-short { display: none; }
@media (max-width: 767.98px) {
    .label-full { display: none; }
    .label-short { display: inline; }
    .booking-block, .free-block { font-size: 0.6rem; padding: 1px; }
    .date-nav { padding: 1rem; }
}
.block-active          { background: #f2c9c9; color: #551414; border: 1px solid #55141440; }
.block-pending_payment { background: #FBF0C8; color: #8a6d1a; border: 1px solid #d4a01750; }
.block-blocked         { background: #E4E0D6; color: #5b5346; border: 1px dashed #5b534660; }
.legend-chip {
    display: inline-block; width: 22px; height: 14px; border-radius: 4px; vertical-align: middle; margin-right: 6px;
}
</style>
@endsection

@section('content')
<div class="schedule-hero">
    <div class="container">
        <h2 class="mb-1" style="font-family:'Baloo 2',sans-serif;font-weight:800;font-size:2.5rem;text-transform:uppercase;">
            <span style="color:var(--cream);">Jadwal</span>
            <span style="color:var(--cream);text-decoration:underline;text-decoration-color:var(--red);text-decoration-thickness:3px;">Meja</span>
        </h2>
        <p class="mb-0" style="color:rgba(244,241,221,0.7)">Pantau ketersediaan meja secara real-time</p>
    </div>
</div>

<div class="container py-4">

    <div class="date-nav">
        <i class="bi bi-calendar3 text-gold"></i>
        <span style="color:var(--ink-mute);font-size:0.9rem;font-weight:700;">Tanggal:</span>
        <input type="date" id="dateFilter" value="{{ $date }}"
            min="{{ today()->toDateString() }}">
        <button onclick="changeDate()" class="btn btn-gold btn-sm">
            <i class="bi bi-search me-1"></i>Lihat
        </button>
        <div class="ms-auto d-flex gap-2 flex-wrap">
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

    <div class="d-flex gap-3 mb-3 flex-wrap" style="font-size:0.8rem;color:rgba(244,241,221,0.85);font-weight:600;">
        <span><span class="legend-chip" style="background:#f2c9c9;border:1px solid #55141440"></span>Sudah Dipesan</span>
        <span><span class="legend-chip" style="background:#FBF0C8;border:1px solid #d4a01750"></span>Menunggu Pembayaran</span>
        <span><span class="legend-chip" style="background:#E4E0D6;border:1px dashed #5b534660"></span>Tidak Tersedia</span>
        <span><span class="legend-chip" style="background:#fff;border:1.5px dashed #2f9e44"></span>Kosong (sisa jam) – klik untuk pesan</span>
        <span><span class="legend-chip" style="background:#f4f1dd;border:1px solid rgba(58,20,20,0.15)"></span>Kotak polos = kosong 1 jam penuh</span>
    </div>
    <div style="overflow-x:auto">
        <div class="table-grid" style="min-width:600px">
            <div class="grid-header">Waktu</div>
            @foreach($tables as $table)
            <div class="grid-header table-name">{{ $table->name }}</div>
            @endforeach

            @foreach($timeSlots as $h => $slot)
            <div class="grid-time">{{ $slot }}</div>
            @foreach($tables as $table)
            @php $row = $grid[$table->id][$h]; @endphp
            <div class="grid-cell {{ $row['past'] ? 'cell-past' : '' }}">
                @if($row['url'])
                    <a href="{{ $row['url'] }}" class="cell-free-link" title="Kosong {{ $slot }} – pesan meja ini">+ Pesan {{ $slot }}</a>
                @endif
                @foreach($row['items'] as $item)
                    @php
                        $top    = ($item['from'] % 60) / 60 * 56;
                        $height = max(($item['to'] - $item['from']) / 60 * 56, 20);
                    @endphp
                    @if($item['type'] === 'free')
                        @if($item['url'])
                        <a href="{{ $item['url'] }}" class="free-block" style="top:{{ $top + 1 }}px;height:{{ $height - 2 }}px" title="{{ $item['label'] }} – klik untuk pesan"><span class="label-full">{{ $item['label'] }}</span><span class="label-short">{{ $item['short'] }}</span></a>
                        @else
                        <div class="free-block" style="top:{{ $top + 1 }}px;height:{{ $height - 2 }}px"><span class="label-full">{{ $item['label'] }}</span><span class="label-short">{{ $item['short'] }}</span></div>
                        @endif
                    @else
                        <div class="booking-block block-{{ $item['type'] }}"
                             style="top:{{ $top + 1 }}px;height:{{ ($item['to'] - $item['from']) / 60 * 56 - 2 }}px;min-height:18px"
                             title="{{ $item['title'] }} {{ $item['label'] }}">
                            {{ $item['closed'] ? '⛔ ' : '' }}{{ $item['label'] }}
                        </div>
                    @endif
                @endforeach
            </div>
            @endforeach
            @endforeach
        </div>
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

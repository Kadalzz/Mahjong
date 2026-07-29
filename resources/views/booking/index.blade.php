@extends('layouts.app')

@section('title', 'Pilih Meja')

@section('head')
<style>
.hero {
    background: var(--green);
    padding: 4rem 0 3rem;
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(ellipse at center, rgba(244,241,221,0.05) 0%, transparent 60%);
    pointer-events: none;
}
.hero .eyebrow {
    font-family: 'Baloo 2', sans-serif;
    font-weight: 700;
    letter-spacing: 2px;
    color: rgba(244,241,221,0.7);
    font-size: 1rem;
}
.hero h1 { font-size: 2.75rem; font-weight: 800; line-height: 1.05; text-transform: uppercase; }
.hero p { color: rgba(244,241,221,0.75); font-size: 1.1rem; }

.table-card {
    background: var(--cream);
    border: 1px solid rgba(58,20,20,0.08);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s;
    cursor: pointer;
    text-decoration: none;
    display: block;
    color: var(--ink);
}
.table-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    border-color: rgba(85,20,20,0.3);
    color: var(--ink);
}
.table-card.unavailable {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}
.table-card.maintenance {
    opacity: 0.55;
    cursor: not-allowed;
    pointer-events: none;
}
.table-body { padding: 1.5rem; }
.table-icon-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; }
.table-name { font-family: 'Baloo 2', sans-serif; font-weight: 700; font-size: 1.15rem; text-transform: uppercase; margin-bottom: 0.25rem; color: var(--ink); }
.table-price { color: var(--red); font-weight: 800; font-size: 1.3rem; font-family: 'Baloo 2', sans-serif; }
.table-price small { font-size: 0.75rem; font-weight: 600; color: var(--ink-mute); }
.table-capacity { font-size: 0.85rem; color: var(--ink-mute); }

.table-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.3em 0.9em;
    border-radius: 20px;
    white-space: nowrap;
}
.status-available { background: #DCEFDD; color: #1f7a34; }
.status-occupied   { background: #F6DEDE; color: var(--red); }
.status-maintenance{ background: #FBF0C8; color: #8a6d1a; }

.section-filter {
    background: transparent;
    border: 2px solid rgba(244,241,221,0.35);
    border-radius: 20px;
    padding: 1.25rem 1.75rem;
    margin-bottom: 2rem;
}
.section-filter h5 { font-family: 'Baloo 2', sans-serif; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--cream); }
.legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 0.35rem; }

.table-icon { flex-shrink: 0; }
</style>
@endsection

@section('content')
<div class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-9">
                <div class="eyebrow">WELCOME TO</div>
                <h1 class="mb-3">Hóng Zhōng<br>Mahjong</h1>
                <p class="mb-4">Pilih meja favorit Anda dan nikmati permainan Mahjong bersama.<br>Reservasi mudah, cepat, dan aman.</p>
                <a href="{{ route('schedule.index') }}" class="btn btn-gold">
                    <i class="bi bi-calendar3 me-2"></i>Lihat Jadwal
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="section-filter">
        <div class="row align-items-center g-3">
            <div class="col">
                <h5 class="mb-0">Pilih Meja</h5>
            </div>
            <div class="col-auto">
                <div class="d-flex gap-3 align-items-center flex-wrap" style="font-size:0.85rem;color:rgba(244,241,221,0.85);font-weight:600;">
                    <span><span class="legend-dot" style="background:#2f9e44"></span>Available</span>
                    <span><span class="legend-dot" style="background:#551414"></span>Booked</span>
                    <span><span class="legend-dot" style="background:#d4a017"></span>Maintenance</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @forelse($tables as $table)
        <div class="col-md-6 col-lg-4">
            @if($table->status === 'available')
            <a href="{{ route('booking.create', $table) }}" class="table-card">
            @else
            <div class="table-card {{ $table->status === 'occupied' ? 'unavailable' : 'maintenance' }}">
            @endif
                <div class="table-body">
                    <div class="table-icon-row">
                        <div>
                            <div class="table-name">{{ $table->name }}</div>
                        </div>
                        <span class="table-status status-{{ $table->status }}">
                            @if($table->status === 'available') Available
                            @elseif($table->status === 'occupied') Booked
                            @else Maintenance
                            @endif
                        </span>
                    </div>
                    <div class="table-price mt-2">
                        Rp {{ number_format($table->getCurrentPricePerHour(), 0, ',', '.') }}
                        <small>/jam</small>
                    </div>
                    <div class="table-capacity mt-2 d-flex align-items-start gap-2">
                        <svg class="table-icon" width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="4" y="4" width="48" height="48" rx="10" fill="#0e300f"/>
                            <rect x="10" y="10" width="36" height="36" rx="6" stroke="#f4f1dd" stroke-opacity="0.25" stroke-width="1.5"/>
                            <rect x="22" y="2" width="12" height="7" rx="2" fill="#0e300f"/>
                            <rect x="22" y="47" width="12" height="7" rx="2" fill="#0e300f"/>
                            <rect x="2" y="22" width="7" height="12" rx="2" fill="#0e300f"/>
                            <rect x="47" y="22" width="7" height="12" rx="2" fill="#0e300f"/>
                        </svg>
                        <div>
                            <div><i class="bi bi-people me-1"></i>{{ $table->capacity }} pemain</div>
                            @if($table->description)
                            <div style="font-size:0.78rem;"><i class="bi bi-info-circle me-1"></i>{{ $table->description }}</div>
                            @endif
                        </div>
                    </div>
                    @if($table->status === 'available')
                    <div class="mt-3">
                        <span class="btn btn-gold btn-sm w-100">
                            PESAN SEKARANG
                        </span>
                    </div>
                    @endif
                </div>
            @if($table->status === 'available')
            </a>
            @else
            </div>
            @endif
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <div style="font-size:3rem;opacity:0.3">🀄</div>
            <p style="color:rgba(244,241,221,0.6)">Belum ada meja tersedia.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

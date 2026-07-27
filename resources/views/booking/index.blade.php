@extends('layouts.app')

@section('title', 'Pilih Meja')

@section('head')
<style>
.hero {
    background: linear-gradient(135deg, #0f0f1a 0%, #1a1035 50%, #0f0f1a 100%);
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
    background: radial-gradient(ellipse at center, rgba(201,168,76,0.05) 0%, transparent 60%);
    pointer-events: none;
}
.hero h1 { font-size: 2.5rem; font-weight: 700; }
.hero p { color: #8888aa; font-size: 1.1rem; }

.table-card {
    background: #1a1a2e;
    border: 1px solid #2a2a45;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s;
    cursor: pointer;
    text-decoration: none;
    display: block;
    color: inherit;
}
.table-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    border-color: rgba(201,168,76,0.4);
    color: inherit;
}
.table-card.unavailable {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}
.table-card.maintenance {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}
.table-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #16213e, #0f3460);
    position: relative;
}
.table-number {
    font-size: 3rem;
    text-align: center;
    line-height: 1;
    margin-bottom: 0.5rem;
}
.table-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.3em 0.8em;
    border-radius: 20px;
}
.status-available { background: #16a08520; color: #1abc9c; border: 1px solid #16a08540; }
.status-occupied   { background: #c0392b20; color: #e74c3c; border: 1px solid #c0392b40; }
.status-maintenance{ background: #f39c1220; color: #f1c40f; border: 1px solid #f39c1240; }

.table-body { padding: 1.25rem 1.5rem; }
.table-name { font-weight: 600; font-size: 1rem; margin-bottom: 0.25rem; }
.table-price { color: var(--gold); font-weight: 700; font-size: 1.2rem; }
.table-price small { font-size: 0.75rem; font-weight: 400; color: #666; }
.table-capacity { font-size: 0.8rem; color: #777; }

.section-filter {
    background: #1a1a2e;
    border: 1px solid #2a2a45;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 2rem;
}
</style>
@endsection

@section('content')
<div class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>Selamat Datang di <span class="text-gold">Mahjong Club</span> 🀄</h1>
                <p>Pilih meja favorit Anda dan nikmati permainan Mahjong bersama. Reservasi mudah, cepat, dan aman.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-calendar3 me-2"></i>Lihat Jadwal
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block" style="font-size:6rem;opacity:0.15">🀄</div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="section-filter">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0 fw-600">Pilih Meja</h5>
                <small class="text-muted">{{ $tables->where('status','available')->count() }} meja tersedia dari {{ $tables->count() }} meja</small>
            </div>
            <div class="col-auto">
                <div class="d-flex gap-3 align-items-center" style="font-size:0.8rem;">
                    <span><span class="table-status status-available me-1">●</span>Tersedia</span>
                    <span><span class="table-status status-occupied me-1">●</span>Penuh</span>
                    <span><span class="table-status status-maintenance me-1">●</span>Maintenance</span>
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
                <div class="table-header">
                    <div class="table-number">🀄</div>
                    <div class="text-center mt-2">
                        <span class="table-status status-{{ $table->status }}">
                            @if($table->status === 'available') <i class="bi bi-check-circle-fill"></i> Tersedia
                            @elseif($table->status === 'occupied') <i class="bi bi-x-circle-fill"></i> Terisi
                            @else <i class="bi bi-tools"></i> Maintenance
                            @endif
                        </span>
                    </div>
                </div>
                <div class="table-body">
                    <div class="table-name">{{ $table->name }}</div>
                    <div class="table-price">
                        Rp {{ number_format($table->getCurrentPricePerHour(), 0, ',', '.') }}
                        <small>/ jam</small>
                    </div>
                    <div class="table-capacity mt-2">
                        <i class="bi bi-people me-1"></i>{{ $table->capacity }} pemain
                        @if($table->description)
                        <br><i class="bi bi-info-circle me-1 mt-1"></i>{{ $table->description }}
                        @endif
                    </div>
                    @if($table->status === 'available')
                    <div class="mt-3">
                        <span class="btn btn-gold btn-sm w-100">
                            <i class="bi bi-calendar-plus me-1"></i>Pesan Sekarang
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
            <p class="text-muted">Belum ada meja tersedia.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Beranda')

@section('head')
<style>
.landing-hero {
    background: var(--green);
    padding: 6rem 0 5rem;
    position: relative;
    overflow: hidden;
    text-align: center;
}
.landing-hero::before {
    content: '';
    position: absolute;
    top: -60%;
    left: -20%;
    width: 140%;
    height: 200%;
    background: radial-gradient(ellipse at center, rgba(244,241,221,0.06) 0%, transparent 60%);
    pointer-events: none;
}
.landing-hero .hero-logo { margin-bottom: 1.5rem; opacity: 0.95; }
.landing-hero .eyebrow {
    font-family: 'Baloo 2', sans-serif;
    font-weight: 700;
    letter-spacing: 3px;
    color: rgba(244,241,221,0.65);
    font-size: 0.9rem;
    text-transform: uppercase;
}
.landing-hero h1 {
    font-size: 3.25rem;
    font-weight: 800;
    line-height: 1.05;
    text-transform: uppercase;
    margin: 0.75rem auto 1.25rem;
}
.landing-hero p.lead {
    color: rgba(244,241,221,0.78);
    font-size: 1.15rem;
    max-width: 620px;
    margin: 0 auto 2.25rem;
}
.hero-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
.btn-outline-cream {
    border: 2px solid rgba(244,241,221,0.4);
    color: var(--cream);
    font-weight: 700;
    padding: 0.55rem 1.5rem;
    border-radius: 999px;
    font-family: 'Baloo 2', sans-serif;
    text-decoration: none;
    transition: all 0.3s;
}
.btn-outline-cream:hover { border-color: var(--cream); color: var(--cream); background: rgba(244,241,221,0.08); }

.section-label {
    font-family: 'Baloo 2', sans-serif;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--red);
    font-size: 0.85rem;
}
.section-title { font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem; }

.feature-card {
    background: var(--cream);
    border-radius: 18px;
    padding: 1.75rem;
    height: 100%;
    color: var(--ink);
}
.feature-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: var(--green);
    color: var(--cream);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    margin-bottom: 1rem;
}
.feature-card h5 { font-family: 'Baloo 2', sans-serif; font-weight: 700; margin-bottom: 0.5rem; }
.feature-card p { color: var(--ink-mute); font-size: 0.92rem; margin-bottom: 0; }

.step-item { display: flex; gap: 1.1rem; align-items: flex-start; }
.step-num {
    flex-shrink: 0;
    width: 42px; height: 42px;
    border-radius: 50%;
    background: var(--red);
    color: var(--cream);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Baloo 2', sans-serif;
    font-weight: 800;
}
.step-item h6 { font-family: 'Baloo 2', sans-serif; font-weight: 700; margin-bottom: 0.3rem; }
.step-item p { color: rgba(244,241,221,0.7); font-size: 0.92rem; margin-bottom: 0; }

.preview-table-card {
    background: var(--cream);
    border-radius: 18px;
    padding: 1.4rem;
    color: var(--ink);
    height: 100%;
}
.preview-table-card .name { font-family: 'Baloo 2', sans-serif; font-weight: 700; text-transform: uppercase; }
.preview-table-card .price { color: var(--red); font-weight: 800; font-family: 'Baloo 2', sans-serif; font-size: 1.1rem; }

.cta-banner {
    background: var(--green-deep);
    border-radius: 24px;
    padding: 3rem 2rem;
    text-align: center;
    margin: 1rem 0 3rem;
}
.cta-banner h2 { font-weight: 800; text-transform: uppercase; margin-bottom: 0.75rem; }
.cta-banner p { color: rgba(244,241,221,0.75); margin-bottom: 1.5rem; }
</style>
@endsection

@section('content')
<div class="landing-hero">
    <div class="container">
        <div class="hero-logo">
            <x-brand-logo :size="64" />
        </div>
        <div class="eyebrow">Selamat Datang di</div>
        <h1>Hóng Zhōng<br>Mahjong</h1>
        <p class="lead">Reservasi meja Mahjong secara online — pilih meja, tentukan jadwal, dan bayar dengan aman. Tanpa antre, tanpa ribet.</p>
        <div class="hero-actions">
            <a href="{{ route('booking.index') }}" class="btn btn-gold">
                <i class="bi bi-table me-2"></i>Pesan Meja Sekarang
            </a>
            <a href="{{ route('schedule.index') }}" class="btn-outline-cream">
                <i class="bi bi-calendar3 me-2"></i>Lihat Jadwal
            </a>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="text-center mb-5">
        <div class="section-label">Kenapa Kami</div>
        <h2 class="section-title">Reservasi Lebih Mudah</h2>
    </div>
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon"><i class="bi bi-clock-history"></i></div>
                <h5>Buka 24 Jam</h5>
                <p>Pesan meja kapan saja, dari mana saja, tanpa perlu telepon atau datang langsung.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                <h5>Pembayaran Aman</h5>
                <p>Transaksi diproses lewat payment gateway terpercaya, cepat dan terenkripsi.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon"><i class="bi bi-receipt"></i></div>
                <h5>Invoice Online</h5>
                <p>Bukti booking dan invoice bisa langsung dilihat di website setelah pembayaran.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon"><i class="bi bi-calendar2-check"></i></div>
                <h5>Jadwal Transparan</h5>
                <p>Lihat status setiap meja dan waiting list secara real-time sebelum memesan.</p>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="section-label">Meja Kami</div>
            <h2 class="section-title mb-0">Pilihan Meja</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('booking.index') }}" class="btn-outline-cream">
                Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="row g-4">
        @forelse($tables->take(3) as $table)
        <div class="col-md-6 col-lg-4">
            <div class="preview-table-card">
                <div class="name mb-1">{{ $table->name }}</div>
                <div class="price mb-2">Rp {{ number_format($table->getCurrentPricePerHour(), 0, ',', '.') }} <small style="font-size:0.7rem;font-weight:600;color:var(--ink-mute)">/jam</small></div>
                <div style="font-size:0.85rem;color:var(--ink-mute)"><i class="bi bi-people me-1"></i>{{ $table->capacity }} pemain</div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-4">
            <p style="color:rgba(244,241,221,0.6)">Belum ada meja tersedia saat ini.</p>
        </div>
        @endforelse
    </div>
</div>

<div class="container py-5">
    <div class="text-center mb-5">
        <div class="section-label">Alurnya</div>
        <h2 class="section-title">Cara Pesan</h2>
    </div>
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="step-item">
                <div class="step-num">1</div>
                <div>
                    <h6>Pilih Meja</h6>
                    <p>Cek ketersediaan lalu pilih meja yang Anda mau.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="step-item">
                <div class="step-num">2</div>
                <div>
                    <h6>Isi Data & Waktu</h6>
                    <p>Masukkan nama, nomor HP, dan jam bermain.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="step-item">
                <div class="step-num">3</div>
                <div>
                    <h6>Bayar Online</h6>
                    <p>Selesaikan pembayaran dengan aman lewat link yang diberikan.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="step-item">
                <div class="step-num">4</div>
                <div>
                    <h6>Datang & Main</h6>
                    <p>Tunjukkan invoice dari website, meja siap digunakan.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="cta-banner">
        <h2>Siap Untuk Bermain?</h2>
        <p>Pesan meja Anda sekarang, hanya butuh beberapa menit.</p>
        <a href="{{ route('booking.index') }}" class="btn btn-gold">
            <i class="bi bi-table me-2"></i>Pesan Meja Sekarang
        </a>
    </div>
</div>
@endsection

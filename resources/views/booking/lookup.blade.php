@extends('layouts.app')

@section('title', 'Cek Booking Saya')

@section('head')
<style>
.lookup-hero {
    background: var(--green);
    padding: 3rem 0 2.5rem;
    text-align: center;
}
.lookup-hero .eyebrow {
    font-family: 'Baloo 2', sans-serif;
    font-weight: 700;
    letter-spacing: 2px;
    color: rgba(244,241,221,0.7);
    font-size: 0.9rem;
    text-transform: uppercase;
}
.lookup-hero h1 { font-size: 2rem; font-weight: 800; text-transform: uppercase; margin: 0.5rem 0 0.5rem; }
.lookup-hero p { color: rgba(244,241,221,0.75); max-width: 480px; margin: 0 auto; }

.lookup-form-card {
    background: var(--cream);
    border-radius: 20px;
    padding: 1.75rem;
    max-width: 480px;
    margin: -2.5rem auto 0;
    position: relative;
    color: var(--ink);
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
}

.result-card {
    background: var(--cream);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    color: var(--ink);
    margin-bottom: 1rem;
}
.result-code {
    font-family: monospace;
    font-weight: 700;
    letter-spacing: 1px;
    color: var(--red);
}
.result-meta { font-size: 0.85rem; color: var(--ink-mute); }

.badge-status { padding: 0.35em 0.85em; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }
.badge-pending_payment { background: #FBF0C8; color: #8a6d1a; }
.badge-waiting          { background: #DCE8F7; color: #2e6fba; }
.badge-active            { background: #DCEFDD; color: #1f7a34; }
.badge-done              { background: rgba(58,20,20,0.08); color: var(--ink-mute); }
.badge-cancelled         { background: #F6DEDE; color: var(--red); }
</style>
@endsection

@section('content')
<div class="lookup-hero">
    <div class="container">
        <div class="eyebrow">Cari Booking</div>
        <h1>Cek Booking Saya</h1>
        <p>Lupa menyimpan kode booking? Cari lagi pakai nomor HP yang dipakai saat memesan.</p>
    </div>
</div>

<div class="container">
    <div class="lookup-form-card">
        <form method="GET" action="{{ route('booking.lookup') }}">
            <label class="form-label small" style="color:var(--ink-mute)">Nomor HP / WhatsApp</label>
            <div class="input-group">
                <span class="input-group-text" style="background:var(--red);border:none;color:var(--cream);border-radius:999px 0 0 999px;font-weight:700;">+62</span>
                <input type="tel" name="phone" value="{{ request('phone') }}"
                    class="form-control"
                    style="border-radius:0;border:none;background:rgba(58,20,20,0.06);color:var(--ink);"
                    placeholder="8xx xxxx xxxx" required>
                <button type="submit" class="btn btn-gold" style="border-radius:0 999px 999px 0;">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="py-5" style="max-width:640px;margin:0 auto;">
        @if($searched)
            @forelse($bookings as $booking)
            <div class="result-card">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                    <div>
                        <div class="result-code">{{ $booking->booking_code }}</div>
                        <div class="result-meta mt-1">{{ $booking->table->name }} &middot; {{ $booking->booking_date->translatedFormat('d M Y') }} &middot; {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</div>
                    </div>
                    <span class="badge-status badge-{{ $booking->status }}">{{ $booking->status_label }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-700" style="color:var(--red)">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</div>
                    @if(in_array($booking->status, ['active', 'done']))
                    <a href="{{ route('booking.invoice', $booking->booking_code) }}" class="btn btn-sm btn-gold">
                        <i class="bi bi-receipt me-1"></i>Lihat Invoice
                    </a>
                    @else
                    <a href="{{ route('booking.confirm', $booking->booking_code) }}" class="btn btn-sm btn-outline-secondary">
                        Lihat Status
                    </a>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-4">
                <div style="font-size:2.5rem;opacity:0.3">🀄</div>
                <p style="color:rgba(244,241,221,0.65)">Tidak ada booking ditemukan untuk nomor HP ini.</p>
            </div>
            @endforelse
        @endif
    </div>
</div>
@endsection

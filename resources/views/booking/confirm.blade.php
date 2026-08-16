@extends('layouts.app')

@section('title', 'Konfirmasi Booking')

@section('head')
<style>
.confirm-wrapper {
    min-height: 80vh;
    display: flex;
    align-items: center;
    padding: 3rem 0;
}
.confirm-card {
    background: var(--cream);
    border-radius: 24px;
    overflow: hidden;
    max-width: 560px;
    margin: 0 auto;
    width: 100%;
    color: var(--ink);
}
.confirm-header {
    padding: 2.5rem 2rem;
    text-align: center;
    background: rgba(85,20,20,0.06);
    border-bottom: 1px solid rgba(58,20,20,0.08);
}
.confirm-header h4 { font-family: 'Baloo 2', sans-serif; color: var(--ink); text-transform: uppercase; }
.confirm-icon {
    width: 80px; height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
}
.icon-success { background: #DCEFDD; border: 2px solid #2f9e4460; color: #1f7a34; }
.icon-waiting  { background: #DCE8F7; border: 2px solid #2e6fba60; color: #2e6fba; }
.booking-code {
    font-family: monospace;
    font-size: 1.6rem;
    font-weight: 700;
    letter-spacing: 2px;
    color: var(--red);
    background: rgba(85,20,20,0.08);
    padding: 0.5rem 1.5rem;
    border-radius: 10px;
    border: 1px solid rgba(85,20,20,0.15);
}
.confirm-body { padding: 2rem; }
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(58,20,20,0.08);
    font-size: 0.9rem;
}
.info-row:last-child { border-bottom: none; }
.info-label { color: var(--ink-mute); }
.info-value { font-weight: 700; text-align: right; }
.total-row .info-value { color: var(--red); font-size: 1.3rem; font-weight: 800; }
</style>
@endsection

@section('content')
<div class="confirm-wrapper">
    <div class="container">
        <div class="confirm-card">
            <div class="confirm-header">
                @if($booking->status === 'waiting')
                <div class="confirm-icon icon-waiting">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <h4 class="mb-1">Masuk Waiting List!</h4>
                <p class="text-muted mb-3" style="font-size:0.9rem">
                    Slot yang dipilih sudah penuh. Anda akan dihubungi jika ada ketersediaan.
                </p>
                @else
                <div class="confirm-icon icon-success">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h4 class="mb-1">Booking Berhasil!</h4>
                <p class="text-muted mb-3" style="font-size:0.9rem">Silakan selesaikan pembayaran untuk mengkonfirmasi reservasi.</p>
                @endif

                <div class="booking-code">{{ $booking->booking_code }}</div>
                <p class="mt-2 mb-0" style="font-size:0.75rem;color:#555">
                    Simpan kode ini untuk referensi Anda. Lupa kode? Cari lagi lewat
                    <a href="{{ route('booking.lookup') }}" style="color:var(--red);font-weight:700;">Cek Booking</a>
                    pakai nomor HP.
                </p>
            </div>

            <div class="confirm-body">
                <div class="info-row">
                    <span class="info-label">Meja</span>
                    <span class="info-value">{{ $booking->table->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Pemesan</span>
                    <span class="info-value">{{ $booking->customer_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">No. HP</span>
                    <span class="info-value">{{ $booking->customer_phone }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal</span>
                    <span class="info-value">{{ $booking->booking_date->translatedFormat('l, d F Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Waktu</span>
                    <span class="info-value">{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Durasi</span>
                    <span class="info-value">{{ $booking->duration_hours }} jam</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <span class="badge-status-{{ $booking->status === 'pending_payment' ? 'pending' : $booking->status }}
                              px-3 py-1 rounded-pill" style="
                              @if($booking->status === 'waiting') background:#2980b920;color:#3498db;border:1px solid #2980b940;
                              @elseif($booking->status === 'pending_payment') background:#f39c1220;color:#f1c40f;border:1px solid #f39c1240;
                              @endif
                              font-size:0.8rem;">
                            {{ $booking->status_label }}
                        </span>
                    </span>
                </div>
                <div class="info-row total-row">
                    <span class="info-label">Total Bayar</span>
                    <span class="info-value">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                </div>

                <div class="mt-4 d-flex flex-column gap-2">
                    @if($booking->status === 'pending_payment' && $booking->payment_url)
                    <a href="{{ $booking->payment_url }}" class="btn btn-gold btn-lg w-100" target="_blank">
                        <i class="bi bi-credit-card me-2"></i>Bayar Sekarang
                    </a>
                    @elseif(in_array($booking->status, ['active', 'done']))
                    <a href="{{ route('booking.invoice', $booking->booking_code) }}" class="btn btn-gold btn-lg w-100">
                        <i class="bi bi-receipt me-2"></i>Lihat Invoice
                    </a>
                    @endif

                    <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-calendar3 me-2"></i>Lihat Jadwal
                    </a>
                    <a href="{{ route('booking.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

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
.icon-pending { background: #FBF0C8; border: 2px solid #d4a01760; color: #8a6d1a; }
.icon-failed  { background: #F6DADA; border: 2px solid #b0303060; color: #9b2c2c; }
.hold-timer {
    display: inline-block;
    font-family: monospace;
    font-size: 1.8rem;
    font-weight: 700;
    color: #8a6d1a;
    background: #FBF0C8;
    padding: 0.25rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1rem;
}
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

@php
    $awaitingPayment = $order->isAwaitingPayment();
    $needsRefund     = $order->needsRefund();
    $isPaid          = $order->isPaid();
    $paymentExpired  = !$needsRefund && !$isPaid && ($order->isPaymentExpired() || ($order->status === 'cancelled' && $order->bookings->every(fn ($b) => $b->cancelled_at === null)));
    $secondsLeft     = $awaitingPayment ? max(0, (int) now()->diffInSeconds($order->holdExpiresAt(), false)) : 0;
@endphp

@section('content')
<div class="confirm-wrapper">
    <div class="container">
        @if($order->status === 'active')
        <div id="redirectBanner" class="text-center py-2 px-3 mb-3 rounded-3" style="max-width:560px;margin:0 auto 1rem;background:#DCEFDD;color:#1f7a34;font-size:0.85rem;">
            <i class="bi bi-check-circle-fill me-1"></i>
            Pembayaran berhasil! Kembali ke halaman Pesan Meja dalam <span id="redirectCountdown">60</span> detik&hellip;
            <button type="button" id="cancelRedirect" class="btn btn-sm btn-link p-0 ms-1" style="font-size:0.85rem;color:#1f7a34;text-decoration:underline;">Batal</button>
        </div>
        @endif
        <div class="confirm-card">
            <div class="confirm-header">
                @if($awaitingPayment)
                <div class="confirm-icon icon-pending">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
                <h4 class="mb-1">Selesaikan Pembayaran</h4>
                <p class="text-muted mb-2" style="font-size:0.9rem">Slot ditahan untuk Anda. Bayar dengan QRIS sebelum waktu habis.</p>
                <div class="hold-timer" id="holdTimer">{{ sprintf('%02d:%02d', intdiv($secondsLeft, 60), $secondsLeft % 60) }}</div>
                @elseif($needsRefund)
                <div class="confirm-icon icon-failed">
                    <i class="bi bi-exclamation-lg"></i>
                </div>
                <h4 class="mb-1">{{ $isPaid ? 'Sebagian Meja Sudah Terisi' : 'Slot Sudah Terisi' }}</h4>
                <p class="text-muted mb-3" style="font-size:0.9rem">Pembayaran Anda diterima setelah waktu bayar habis, dan {{ $isPaid ? 'sebagian meja' : 'slot' }} sudah dipesan orang lain. Tunjukkan kode ini ke kasir untuk pengembalian dana{{ $isPaid ? ' meja yang ditandai' : '' }}.</p>
                @elseif($paymentExpired)
                <div class="confirm-icon icon-failed">
                    <i class="bi bi-x-lg"></i>
                </div>
                <h4 class="mb-1">Waktu Pembayaran Habis</h4>
                <p class="text-muted mb-3" style="font-size:0.9rem">Booking ini dibatalkan karena tidak dibayar dalam {{ \App\Models\Booking::holdMinutes() }} menit. Silakan pesan ulang.</p>
                @elseif($order->status === 'cancelled')
                <div class="confirm-icon icon-failed">
                    <i class="bi bi-x-lg"></i>
                </div>
                <h4 class="mb-1">Booking Dibatalkan</h4>
                <p class="text-muted mb-3" style="font-size:0.9rem">Hubungi kasir jika ada pertanyaan.</p>
                @else
                <div class="confirm-icon icon-success">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h4 class="mb-1">{{ $order->status === 'done' ? 'Booking Selesai' : 'Booking Berhasil!' }}</h4>
                <p class="text-muted mb-3" style="font-size:0.9rem">Meja menyala otomatis pada jam mulai main masing-masing.</p>
                @endif

                <div class="booking-code">{{ $order->code }}</div>
                <p class="mt-2 mb-0" style="font-size:0.75rem;color:#555">
                    Simpan kode ini untuk referensi Anda. Lupa kode? Cari lagi lewat
                    <a href="{{ route('booking.lookup') }}" style="color:var(--red);font-weight:700;">Cek Booking</a>
                    pakai nama atau nomor HP.
                </p>
            </div>

            <div class="confirm-body">
                <div class="info-row">
                    <span class="info-label">Pemesan</span>
                    <span class="info-value">{{ $order->customer_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">No. HP</span>
                    <span class="info-value">{{ $order->customer_phone }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal</span>
                    <span class="info-value">{{ $order->booking_date->translatedFormat('l, d F Y') }}</span>
                </div>
                @foreach($order->bookings as $line)
                <div class="info-row">
                    <span class="info-label">
                        <span style="font-weight:700;color:var(--ink)">{{ $line->table->name }}</span><br>
                        <span style="font-size:0.8rem">{{ $line->time_range_label }} &bull; {{ $line->duration_hours }} jam</span>
                        @if($line->refund_status === 'pending_refund')
                        <br><span style="font-size:0.75rem;color:#9b2c2c;font-weight:700">Slot terisi orang lain — dana dikembalikan kasir</span>
                        @endif
                    </span>
                    <span class="info-value">Rp {{ number_format($line->total_price, 0, ',', '.') }}</span>
                </div>
                @endforeach
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <span class="px-3 py-1 rounded-pill" style="
                              @if($awaitingPayment) background:#f39c1220;color:#b7791f;border:1px solid #f39c1240;
                              @elseif($isPaid) background:#2f9e4420;color:#1f7a34;border:1px solid #2f9e4440;
                              @else background:#b0303018;color:#9b2c2c;border:1px solid #b0303040;
                              @endif
                              font-size:0.8rem;">
                            {{ $paymentExpired ? 'Waktu Bayar Habis' : $order->status_label }}
                        </span>
                    </span>
                </div>
                <div class="info-row total-row">
                    <span class="info-label">Total Bayar</span>
                    <span class="info-value">Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                </div>

                <div class="mt-4 d-flex flex-column gap-2">
                    @if($awaitingPayment && $order->payment_url)
                    <a href="{{ $order->payment_url }}" id="payButton" class="btn btn-gold btn-lg w-100" target="_blank" rel="noopener">
                        <i class="bi bi-qr-code me-2"></i>Bayar dengan QRIS
                    </a>
                    @elseif($isPaid)
                    <a href="{{ route('booking.invoice', $order->code) }}" class="btn btn-gold btn-lg w-100">
                        <i class="bi bi-receipt me-2"></i>Lihat Invoice
                    </a>
                    @elseif($paymentExpired)
                    <a href="{{ route('booking.create', $order->bookings->first()?->mahjong_table_id ?? 1) }}" class="btn btn-gold btn-lg w-100">
                        <i class="bi bi-arrow-repeat me-2"></i>Pesan Ulang
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

@section('scripts')
<script>
(function () {
    @if($order->status === 'active')
    let seconds = 60;
    const el = document.getElementById('redirectCountdown');
    const banner = document.getElementById('redirectBanner');
    const cancelBtn = document.getElementById('cancelRedirect');
    let cancelled = false;

    const interval = setInterval(function () {
        seconds--;
        if (el) el.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(interval);
            if (!cancelled) {
                window.location.href = "{{ route('booking.index') }}";
            }
        }
    }, 1000);

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            cancelled = true;
            clearInterval(interval);
            if (banner) banner.style.display = 'none';
        });
    }
    @elseif($awaitingPayment)
    let remaining = {{ $secondsLeft }};
    const timer = document.getElementById('holdTimer');
    const statusUrl = "{{ route('booking.status', $order->code) }}";

    const tick = setInterval(function () {
        remaining = Math.max(0, remaining - 1);
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        if (timer) timer.textContent = m + ':' + s;
        if (remaining <= 0) {
            clearInterval(tick);
            setTimeout(function () { window.location.reload(); }, 3000);
        }
    }, 1000);

    const poll = setInterval(function () {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status !== 'pending_payment') {
                    clearInterval(poll);
                    window.location.reload();
                }
            })
            .catch(function () {});
    }, 4000);
    @endif
})();
</script>
@endsection

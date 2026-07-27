@extends('layouts.app')

@section('title', 'Invoice ' . $booking->booking_code)

@section('head')
<style>
.invoice-wrap {
    min-height: 80vh;
    padding: 3rem 0;
}
.invoice-card {
    background: #1a1a2e;
    border: 1px solid #2a2a45;
    border-radius: 24px;
    max-width: 640px;
    margin: 0 auto;
    overflow: hidden;
}
.invoice-header {
    padding: 2rem;
    background: linear-gradient(135deg, #1e1e3a, #16213e);
    border-bottom: 1px solid #2a2a45;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
}
.invoice-body { padding: 2rem; }
.invoice-row {
    display: flex;
    justify-content: space-between;
    padding: 0.6rem 0;
    border-bottom: 1px solid #2a2a4520;
    font-size: 0.9rem;
}
.invoice-row:last-child { border-bottom: none; }
.invoice-label { color: #888; }
.invoice-value { font-weight: 500; text-align: right; }
.invoice-total .invoice-value { color: var(--gold); font-size: 1.4rem; font-weight: 700; }
.invoice-code {
    font-family: monospace;
    font-size: 1.3rem;
    font-weight: 700;
    letter-spacing: 1px;
    color: var(--gold);
}
.badge-s-active { background: #16a08520; color: #1abc9c; border: 1px solid #16a08540; padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.8rem; }
.badge-s-done   { background: #ffffff10; color: #aaa;     border: 1px solid #ffffff20; padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.8rem; }
@media print {
    nav, footer, .no-print { display: none !important; }
    body { background: #fff !important; color: #000 !important; }
    .invoice-card { border: none !important; background: #fff !important; }
    .invoice-header { background: #fff !important; border-bottom: 2px solid #000 !important; }
    .invoice-label, .invoice-value, .invoice-code { color: #000 !important; }
}
</style>
@endsection

@section('content')
<div class="invoice-wrap">
    <div class="container">
        <div class="invoice-card">
            <div class="invoice-header">
                <div>
                    <div style="font-weight:700;font-size:1.2rem;">🀄 <span class="text-gold">Mahjong Club</span></div>
                    <div style="font-size:0.8rem;color:#888;margin-top:0.25rem;">Sistem Reservasi Meja Mahjong</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:0.75rem;color:#888;">No. Invoice</div>
                    <div class="invoice-code">{{ $booking->booking_code }}</div>
                    <div style="font-size:0.75rem;color:#888;margin-top:0.4rem;">
                        {{ ($booking->transaction->paid_at ?? now())->translatedFormat('d F Y, H:i') }} WIB
                    </div>
                </div>
            </div>

            <div class="invoice-body">
                <div class="invoice-row">
                    <span class="invoice-label">Pelanggan</span>
                    <span class="invoice-value">{{ $booking->customer_name }}</span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">No. HP</span>
                    <span class="invoice-value">{{ $booking->customer_phone }}</span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Meja</span>
                    <span class="invoice-value">{{ $booking->table->name }}</span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Tanggal</span>
                    <span class="invoice-value">{{ $booking->booking_date->translatedFormat('l, d F Y') }}</span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Waktu</span>
                    <span class="invoice-value">{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }} ({{ $booking->duration_hours }} jam)</span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Metode Bayar</span>
                    <span class="invoice-value">{{ $booking->transaction->payment_method ?? '—' }}</span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Status</span>
                    <span class="invoice-value">
                        <span class="badge-s-{{ $booking->status }}">{{ $booking->status_label }}</span>
                    </span>
                </div>
                <div class="invoice-row invoice-total">
                    <span class="invoice-label">Total Dibayar</span>
                    <span class="invoice-value">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                </div>

                <div class="mt-4 d-flex gap-2 no-print">
                    <button onclick="window.print()" class="btn btn-gold flex-grow-1">
                        <i class="bi bi-printer me-2"></i>Cetak / Simpan PDF
                    </button>
                    <a href="{{ route('booking.confirm', $booking->booking_code) }}" class="btn btn-outline-secondary">
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

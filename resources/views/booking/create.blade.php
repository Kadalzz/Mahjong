@extends('layouts.app')

@section('title', 'Pesan Meja')

@section('head')
<style>
.booking-hero {
    background: var(--green);
    padding: 2rem 0;
    border-bottom: 1px solid var(--border);
}
.booking-hero h2 { color: var(--cream); font-weight: 800; text-transform: uppercase; }
.form-card {
    background: var(--cream);
    border-radius: 22px;
    padding: 1.75rem;
    color: var(--ink);
}
.summary-card {
    background: var(--red);
    border-radius: 22px;
    padding: 1.5rem;
    position: sticky;
    top: 90px;
    color: var(--cream);
}
.form-label { color: var(--ink-mute); font-size: 0.875rem; font-weight: 700; margin-bottom: 0.4rem; }
.form-control, .form-select {
    background: var(--red) !important;
    border: none !important;
    color: var(--cream) !important;
    border-radius: 999px;
    padding: 0.75rem 1.25rem;
    font-weight: 700;
    transition: background 0.2s;
}
.form-control:focus, .form-select:focus {
    background: var(--red-light) !important;
    box-shadow: 0 0 0 3px rgba(85,20,20,0.2) !important;
    color: var(--cream) !important;
}
.form-control::placeholder { color: rgba(244,241,221,0.6); }
.price-display {
    font-family: 'Baloo 2', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: var(--cream);
}
.availability-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 0.4em 1em;
    border-radius: 20px;
}
.avail-ok { background: #DCEFDD; color: #1f7a34; }
.avail-no { background: #F6DADA; color: #9b2c2c; }
.avail-checking { background: #FBF0C8; color: #8a6d1a; }
.step-badge {
    width: 28px; height: 28px;
    background: var(--red);
    color: var(--cream);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    margin-right: 0.5rem;
}
.form-card h5 { font-family: 'Baloo 2', sans-serif; font-weight: 700; color: var(--ink); text-transform: uppercase; }
.btn-play-now {
    background: var(--red);
    color: var(--cream);
    border: none;
    border-radius: 999px;
    padding: 0.6rem 1.4rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.btn-play-now:hover { background: var(--red-light); color: var(--cream); }
.busy-chip {
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.3em 0.8em;
    border-radius: 999px;
    margin: 0 0.35rem 0.35rem 0;
    background: rgba(58,20,20,0.1);
    color: var(--ink);
}
.busy-chip.pending { background: #FBF0C8; color: #8a6d1a; }
.submit-hint { font-size: 0.8rem; color: var(--ink-mute); text-align: center; margin-top: 0.5rem; }
.line-row {
    background: rgba(58,20,20,0.05);
    border-radius: 16px;
    padding: 0.9rem 1rem;
    margin-bottom: 0.75rem;
}
.line-row .form-label { margin-bottom: 0.25rem; }
</style>
@endsection

@section('content')
<div class="booking-hero">
    <div class="container">
        <a href="{{ route('booking.index') }}" class="text-decoration-none mb-2 d-inline-block" style="color:rgba(244,241,221,0.7)">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <h2 class="mb-1">Reservasi Meja</h2>
        <p class="mb-0" style="color:rgba(244,241,221,0.7)">Pesan sampai {{ $maxTables }} meja sekaligus dalam satu pembayaran.</p>
    </div>
</div>

<div class="container py-4">
    <form id="bookingForm" action="{{ route('booking.store') }}" method="POST" autocomplete="off">
        @csrf

        <div class="row g-4">
            <div class="col-lg-8">

                <div class="form-card mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <h5 class="mb-0"><span class="step-badge">1</span>Pilih Meja &amp; Waktu</h5>
                        <button type="button" id="playNowBtn" class="btn btn-play-now">
                            <i class="bi bi-lightning-charge-fill me-1"></i>Main Sekarang
                        </button>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-5">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="booking_date" id="booking_date"
                                class="form-control @error('booking_date') is-invalid @enderror"
                                min="{{ today()->toDateString() }}"
                                value="{{ old('booking_date', $prefillDate ?? today()->toDateString()) }}"
                                required>
                            @error('booking_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    @include('booking.partials.lines', ['tables' => $tables, 'maxTables' => $maxTables, 'defaultTableId' => $table->id, 'defaultStart' => $prefillTime ?? ''])
                </div>

                <div class="form-card mb-4">
                    <h5 class="mb-4"><span class="step-badge">2</span>Data Pemesanan</h5>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="customer_name" id="customer_name" autocomplete="off" autocorrect="off" autocapitalize="words" spellcheck="false"
                                class="form-control @error('customer_name') is-invalid @enderror"
                                placeholder="Masukkan nama lengkap"
                                value="{{ old('customer_name') }}"
                                required>
                            @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">No. HP / WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:var(--red);border:none;color:var(--cream);border-radius:999px 0 0 999px;font-weight:700;">+62</span>
                                <input type="tel" name="customer_phone" id="customer_phone" autocomplete="off"
                                    class="form-control @error('customer_phone') is-invalid @enderror"
                                    style="border-radius:0 999px 999px 0 !important;"
                                    placeholder="8xx xxxx xxxx"
                                    inputmode="numeric"
                                    pattern="[0-9]{9,13}"
                                    minlength="9" maxlength="13"
                                    value="{{ old('customer_phone') }}"
                                    required>
                            </div>
                            @error('customer_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <h5 class="mb-3"><span class="step-badge">3</span>Pembayaran</h5>
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-3" style="background:rgba(58,20,20,0.06)">
                        <i class="bi bi-shield-lock-fill" style="font-size:1.5rem;color:var(--red)"></i>
                        <div style="font-size:0.85rem;color:var(--ink-mute)">
                            Dibayar via DOKU — QRIS. Satu pembayaran untuk semua meja. Selesaikan dalam {{ \App\Models\Booking::holdMinutes() }} menit setelah memesan, atau slot akan dilepas.
                        </div>
                    </div>
                    <button type="submit" id="submitBtn" class="btn btn-gold btn-lg w-100" disabled>
                        <i class="bi bi-lock-fill me-2"></i>Lanjut ke Pembayaran
                    </button>
                    <p class="submit-hint" id="submitHint">Pilih tanggal, jam mulai, dan durasi dulu untuk melanjutkan.</p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="summary-card">
                    <div class="text-center mb-3" style="font-size:3rem">🀄</div>
                    <h6 class="mb-3 text-center" style="font-family:'Baloo 2',sans-serif;text-transform:uppercase;">Ringkasan Reservasi</h6>
                    <hr style="border-color:rgba(244,241,221,0.25)">
                    <div class="d-flex justify-content-between mb-2" style="font-size:0.875rem">
                        <span style="opacity:0.75">Tanggal</span>
                        <span id="s-date">—</span>
                    </div>
                    <div id="s-lines" style="font-size:0.85rem"></div>
                    <hr style="border-color:rgba(244,241,221,0.25)">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="opacity:0.75">Total</span>
                        <div class="price-display" id="s-total">—</div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
@include('booking.partials.lines-script')
<script>
const serverEpochMs = {{ now()->getTimestampMs() }};
const serverUtcOffsetMin = {{ now()->utcOffset() }};
const pageLoadedAt = Date.now();

function serverNow() {
    return new Date(serverEpochMs + (Date.now() - pageLoadedAt) + serverUtcOffsetMin * 60000);
}

function formatRupiah(n) {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

const maxAmount = {{ $maxAmount }};
const dateInput = document.getElementById('booking_date');
const submitBtn = document.getElementById('submitBtn');
const submitHint = document.getElementById('submitHint');

function renderSummary(lines) {
    const date = dateInput.value;
    document.getElementById('s-date').textContent = date
        ? new Date(date).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
        : '—';

    document.getElementById('s-lines').innerHTML = lines.map(l => `
        <div class="d-flex justify-content-between mb-2 gap-2">
            <span>${escapeHtml(l.tableName)}<br><span style="opacity:0.7;font-size:0.78rem">${l.start ? `${l.start} – ${l.end.label}${l.end.suffix}` : 'jam belum dipilih'} &bull; ${l.hours} jam</span></span>
            <span class="fw-bold text-nowrap">${formatRupiah(l.price)}</span>
        </div>`).join('');

    const total = lines.reduce((sum, l) => sum + l.price, 0);
    document.getElementById('s-total').textContent = formatRupiah(total);

    const bad = lines.filter(l => l.state === 'bad').length;
    const incomplete = lines.filter(l => l.state === 'incomplete').length;
    const pending = lines.filter(l => l.state === 'pending' || l.state === '').length;

    if (maxAmount > 0 && total > maxAmount) {
        submitBtn.disabled = true;
        submitHint.textContent = `Total melebihi batas pembayaran QRIS (${formatRupiah(maxAmount)}). Kurangi jumlah meja atau durasi, atau hubungi kasir untuk pembayaran tunai.`;
    } else if (bad > 0) {
        submitBtn.disabled = true;
        submitHint.textContent = `${bad} meja bentrok. Ganti jam atau hapus meja yang ditandai merah.`;
    } else if (incomplete > 0) {
        submitBtn.disabled = true;
        submitHint.textContent = 'Pilih jam mulai untuk setiap meja, atau tekan Main Sekarang.';
    } else if (pending > 0) {
        submitBtn.disabled = true;
        submitHint.textContent = 'Mengecek ketersediaan jam...';
    } else {
        submitBtn.disabled = false;
        submitHint.textContent = '';
    }
}

window.initBookingLines({
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
    dateInput,
    playNowBtn: document.getElementById('playNowBtn'),
    serverNow,
    allowPast: false,
    onChange: renderSummary,
});

document.getElementById('playNowBtn').addEventListener('click', () => {
    document.getElementById('customer_name').focus();
});

document.getElementById('customer_phone').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 13);
});
</script>
@endsection

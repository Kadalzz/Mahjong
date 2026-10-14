@extends('layouts.app')

@section('title', 'Pesan ' . $table->name)

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
</style>
@endsection

@section('content')
<div class="booking-hero">
    <div class="container">
        <a href="{{ route('booking.index') }}" class="text-decoration-none mb-2 d-inline-block" style="color:rgba(244,241,221,0.7)">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <h2 class="mb-1">Reservasi {{ $table->name }}</h2>
        <p class="mb-0" style="color:rgba(244,241,221,0.7)">Rp {{ number_format($table->getCurrentPricePerHour(), 0, ',', '.') }} / jam &bull; {{ $table->capacity }} pemain</p>
    </div>
</div>

<div class="container py-4">
    <form id="bookingForm" action="{{ route('booking.store') }}" method="POST">
        @csrf
        <input type="hidden" name="mahjong_table_id" value="{{ $table->id }}">

        <div class="row g-4">
            <div class="col-lg-8">

                <div class="form-card mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <h5 class="mb-0"><span class="step-badge">1</span>Pilih Waktu</h5>
                        <button type="button" id="playNowBtn" class="btn btn-play-now">
                            <i class="bi bi-lightning-charge-fill me-1"></i>Main Sekarang
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="booking_date" id="booking_date"
                                class="form-control @error('booking_date') is-invalid @enderror"
                                min="{{ today()->toDateString() }}"
                                value="{{ old('booking_date', today()->toDateString()) }}"
                                required>
                            @error('booking_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="start_time" id="start_time"
                                class="form-control @error('start_time') is-invalid @enderror"
                                step="60"
                                value="{{ old('start_time') }}"
                                required>
                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Durasi</label>
                            <select name="duration_hours" id="duration_hours" class="form-select" required>
                                @for($i = 1; $i <= 8; $i++)
                                <option value="{{ $i }}" {{ old('duration_hours', 1) == $i ? 'selected' : '' }}>
                                    {{ $i }} Jam
                                </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="mt-3" id="availability-result" style="display:none">
                        <span id="avail-badge" class="availability-badge"></span>
                        <span id="end-time-display" class="ms-2" style="font-size:0.85rem;color:var(--ink-mute)"></span>
                    </div>

                    <div class="mt-3">
                        <div style="font-size:0.8rem;font-weight:700;color:var(--ink-mute);margin-bottom:0.4rem;">Jam yang sudah terisi di tanggal ini</div>
                        <div id="busy-slots" style="font-size:0.8rem;color:var(--ink-mute)">Memuat&hellip;</div>
                    </div>
                </div>

                <div class="form-card mb-4">
                    <h5 class="mb-4"><span class="step-badge">2</span>Data Pemesanan</h5>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="customer_name" id="customer_name"
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
                                <input type="tel" name="customer_phone" id="customer_phone"
                                    class="form-control @error('customer_phone') is-invalid @enderror"
                                    style="border-radius:0 999px 999px 0 !important;"
                                    placeholder="8xx xxxx xxxx"
                                    inputmode="numeric"
                                    pattern="[0-9]{9,13}"
                                    minlength="9" maxlength="13"
                                    value="{{ old('customer_phone') }}"
                                    required>
                            </div>
                            @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <h5 class="mb-3"><span class="step-badge">3</span>Pembayaran</h5>
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-3" style="background:rgba(58,20,20,0.06)">
                        <i class="bi bi-shield-lock-fill" style="font-size:1.5rem;color:var(--red)"></i>
                        <div style="font-size:0.85rem;color:var(--ink-mute)">
                            Dibayar via DOKU — QRIS. Selesaikan pembayaran dalam {{ \App\Models\Booking::holdMinutes() }} menit setelah memesan, atau slot akan dilepas.
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
                        <span style="opacity:0.75">Meja</span>
                        <span class="fw-600">{{ $table->name }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:0.875rem">
                        <span style="opacity:0.75">Tanggal</span>
                        <span id="s-date">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:0.875rem">
                        <span style="opacity:0.75">Waktu</span>
                        <span id="s-time">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:0.875rem">
                        <span style="opacity:0.75">Durasi</span>
                        <span id="s-duration">—</span>
                    </div>
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
<script>
const pricePerHour = {{ $table->getCurrentPricePerHour() }};
const checkUrl = '{{ route("booking.check", $table) }}';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
const serverEpochMs = {{ now()->getTimestampMs() }};
const serverUtcOffsetMin = {{ now()->utcOffset() }};
const pageLoadedAt = Date.now();

let checkTimeout;
let isAvailable = false;

function serverNow() {
    return new Date(serverEpochMs + (Date.now() - pageLoadedAt) + serverUtcOffsetMin * 60000);
}

function formatRupiah(n) {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function formatDate(d) {
    const date = new Date(d);
    return date.toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
}

function computeEnd(start, dur) {
    const [h, m] = start.split(':').map(Number);
    const total = (h * 60 + m) + (dur * 60);
    const wrapped = total % (24 * 60);
    const label = `${String(Math.floor(wrapped / 60)).padStart(2,'0')}:${String(wrapped % 60).padStart(2,'0')}`;
    return total >= 24 * 60 ? `${label} (+1 hari)` : label;
}

function setSubmitState(enabled, hint) {
    document.getElementById('submitBtn').disabled = !enabled;
    const hintEl = document.getElementById('submitHint');
    hintEl.textContent = hint || '';
    hintEl.style.display = hint ? 'block' : 'none';
}

function updateSummary() {
    const date = document.getElementById('booking_date').value;
    const start = document.getElementById('start_time').value;
    const dur = parseInt(document.getElementById('duration_hours').value);

    document.getElementById('s-date').textContent = date ? formatDate(date) : '—';
    document.getElementById('s-duration').textContent = dur ? dur + ' jam' : '—';
    document.getElementById('s-total').textContent = dur ? formatRupiah(pricePerHour * dur) : '—';

    const end = start && dur ? computeEnd(start, dur) : null;
    document.getElementById('s-time').textContent = end ? `${start} – ${end}` : '—';
}

function renderBusy(busy) {
    const box = document.getElementById('busy-slots');
    if (!busy || busy.length === 0) {
        box.innerHTML = 'Belum ada &mdash; semua jam masih kosong.';
        return;
    }
    box.innerHTML = busy.map(b =>
        `<span class="busy-chip ${b.status === 'pending_payment' ? 'pending' : ''}" title="${b.status === 'pending_payment' ? 'Sedang menunggu pembayaran' : 'Sudah dibayar'}">${b.start} – ${b.end}</span>`
    ).join('');
}

function checkAvailability() {
    const date = document.getElementById('booking_date').value;
    const start = document.getElementById('start_time').value;
    const dur = document.getElementById('duration_hours').value;

    updateSummary();

    const badge = document.getElementById('avail-badge');
    const result = document.getElementById('availability-result');
    const endDisplay = document.getElementById('end-time-display');

    if (!date || !dur) {
        setSubmitState(false, 'Pilih tanggal, jam mulai, dan durasi dulu untuk melanjutkan.');
        return;
    }

    if (start) {
        result.style.display = 'block';
        badge.className = 'availability-badge avail-checking';
        badge.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengecek...';
        endDisplay.textContent = '';
    } else {
        result.style.display = 'none';
    }
    setSubmitState(false, start ? 'Mengecek ketersediaan jam...' : 'Pilih jam mulai, atau tekan Main Sekarang.');

    clearTimeout(checkTimeout);
    checkTimeout = setTimeout(() => {
        fetch(checkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ booking_date: date, start_time: start || null, duration_hours: dur }),
        })
        .then(r => r.json())
        .then(data => {
            renderBusy(data.busy);
            if (!start) return;

            isAvailable = data.available;

            if (data.available) {
                badge.className = 'availability-badge avail-ok';
                badge.innerHTML = '<i class="bi bi-check-circle-fill"></i> Tersedia!';
                endDisplay.textContent = `Selesai pukul ${data.end_time}${data.ends_next_day ? ' (besok)' : ''}`;
                setSubmitState(true, '');
            } else {
                badge.className = 'availability-badge avail-no';
                badge.innerHTML = '<i class="bi bi-x-circle-fill"></i> Tidak bisa dipesan';
                endDisplay.textContent = data.message || '';
                setSubmitState(false, data.message || 'Jam ini tidak tersedia. Pilih jam lain.');
            }
        })
        .catch(() => {
            badge.className = 'availability-badge avail-checking';
            badge.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Gagal cek';
            setSubmitState(false, 'Gagal mengecek ketersediaan. Periksa koneksi lalu coba lagi.');
        });
    }, 400);
}

document.getElementById('playNowBtn').addEventListener('click', function () {
    const now = serverNow();
    document.getElementById('booking_date').value = now.toISOString().slice(0, 10);
    document.getElementById('start_time').value =
        `${String(now.getUTCHours()).padStart(2,'0')}:${String(now.getUTCMinutes()).padStart(2,'0')}`;
    checkAvailability();
    document.getElementById('customer_name').focus();
});

['booking_date', 'start_time', 'duration_hours'].forEach(id => {
    document.getElementById(id).addEventListener('change', checkAvailability);
});

document.getElementById('customer_phone').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 13);
});

checkAvailability();
</script>
@endsection

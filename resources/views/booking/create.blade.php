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
.avail-no { background: #DCE8F7; color: #2e6fba; }
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
            <!-- Left: Form -->
            <div class="col-lg-8">

                <!-- Step 1: Date & Time -->
                <div class="form-card mb-4">
                    <h5 class="mb-4"><span class="step-badge">1</span>Pilih Waktu</h5>

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
                            <select name="start_time" id="start_time" class="form-select @error('start_time') is-invalid @enderror" required>
                                <option value="">-- Pilih Jam --</option>
                                @for($h = 8; $h < 24; $h++)
                                <option value="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00"
                                    {{ old('start_time') === str_pad($h, 2, '0', STR_PAD_LEFT).':00' ? 'selected' : '' }}>
                                    {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00
                                </option>
                                @endfor
                            </select>
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
                </div>

                <!-- Step 2: Personal Info -->
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
                                    value="{{ old('customer_phone') }}"
                                    required>
                            </div>
                            @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <!-- Step 3: Payment -->
                <div class="form-card">
                    <h5 class="mb-3"><span class="step-badge">3</span>Pembayaran</h5>
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-3" style="background:rgba(58,20,20,0.06)">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MidtransLogo.png/320px-MidtransLogo.png"
                             alt="Midtrans" height="24">
                        <div style="font-size:0.85rem;color:var(--ink-mute)">
                            Dibayar via Midtrans — GoPay, OVO, DANA, Transfer Bank, Kartu Kredit
                        </div>
                    </div>
                    <button type="submit" id="submitBtn" class="btn btn-gold btn-lg w-100" disabled>
                        <i class="bi bi-lock-fill me-2"></i>Lanjut ke Pembayaran
                    </button>
                    <p class="text-center mt-2" style="font-size:0.78rem;color:var(--ink-mute)">
                        Jika meja sudah penuh, booking akan masuk <strong>Waiting List</strong> otomatis
                    </p>
                </div>
            </div>

            <!-- Right: Summary -->
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
                    <div id="s-waiting-info" class="mt-3 p-2 rounded-2 text-center" style="background:rgba(244,241,221,0.15);font-size:0.8rem;display:none!important">
                        <i class="bi bi-clock me-1"></i>Slot ini penuh — akan masuk Waiting List
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

let checkTimeout;
let isAvailable = false;

function formatRupiah(n) {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function formatDate(d) {
    const date = new Date(d);
    return date.toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
}

function updateSummary() {
    const date = document.getElementById('booking_date').value;
    const start = document.getElementById('start_time').value;
    const dur = parseInt(document.getElementById('duration_hours').value);

    document.getElementById('s-date').textContent = date ? formatDate(date) : '—';
    document.getElementById('s-duration').textContent = dur ? dur + ' jam' : '—';
    document.getElementById('s-total').textContent = dur ? formatRupiah(pricePerHour * dur) : '—';

    if (start && dur) {
        const [h] = start.split(':').map(Number);
        const endH = h + dur;
        document.getElementById('s-time').textContent = `${start} – ${String(endH).padStart(2,'0')}:00`;
    } else {
        document.getElementById('s-time').textContent = '—';
    }
}

function checkAvailability() {
    const date = document.getElementById('booking_date').value;
    const start = document.getElementById('start_time').value;
    const dur = document.getElementById('duration_hours').value;

    updateSummary();

    if (!date || !start || !dur) return;

    const badge = document.getElementById('avail-badge');
    const result = document.getElementById('availability-result');
    const endDisplay = document.getElementById('end-time-display');
    const submitBtn = document.getElementById('submitBtn');

    result.style.display = 'block';
    badge.className = 'availability-badge avail-checking';
    badge.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengecek...';
    submitBtn.disabled = true;

    clearTimeout(checkTimeout);
    checkTimeout = setTimeout(() => {
        fetch(checkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ booking_date: date, start_time: start, duration_hours: dur }),
        })
        .then(r => r.json())
        .then(data => {
            isAvailable = data.available;
            const waitingInfo = document.getElementById('s-waiting-info');

            if (data.available) {
                badge.className = 'availability-badge avail-ok';
                badge.innerHTML = '<i class="bi bi-check-circle-fill"></i> Tersedia!';
                waitingInfo.style.setProperty('display', 'none', 'important');
            } else {
                badge.className = 'availability-badge avail-no';
                badge.innerHTML = '<i class="bi bi-clock-fill"></i> Penuh — Masuk Waiting List';
                waitingInfo.style.removeProperty('display');
            }
            endDisplay.textContent = `Selesai pukul ${data.end_time}`;
            submitBtn.disabled = false;
        })
        .catch(() => {
            badge.className = 'availability-badge avail-checking';
            badge.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Gagal cek';
            submitBtn.disabled = true;
        });
    }, 600);
}

['booking_date', 'start_time', 'duration_hours'].forEach(id => {
    document.getElementById(id).addEventListener('change', checkAvailability);
});

updateSummary();
</script>
@endsection

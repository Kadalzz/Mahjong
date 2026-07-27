@extends('layouts.app')

@section('title', 'Pesan ' . $table->name)

@section('head')
<style>
.booking-hero {
    background: linear-gradient(135deg, #1a1035 0%, #0f0f1a 100%);
    padding: 2.5rem 0;
    border-bottom: 1px solid #2a2a45;
}
.form-card {
    background: #1a1a2e;
    border: 1px solid #2a2a45;
    border-radius: 20px;
    padding: 2rem;
}
.summary-card {
    background: linear-gradient(135deg, #1e1e3a, #16213e);
    border: 1px solid rgba(201,168,76,0.2);
    border-radius: 20px;
    padding: 1.5rem;
    position: sticky;
    top: 80px;
}
.form-label { color: #aaa; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.4rem; }
.form-control, .form-select {
    background: #0f0f1a !important;
    border: 1px solid #2a2a45 !important;
    color: #e0e0e0 !important;
    border-radius: 10px;
    padding: 0.7rem 1rem;
    transition: border-color 0.2s;
}
.form-control:focus, .form-select:focus {
    border-color: rgba(201,168,76,0.5) !important;
    box-shadow: 0 0 0 3px rgba(201,168,76,0.1) !important;
}
.form-control::placeholder { color: #444; }
.price-display {
    font-size: 2rem;
    font-weight: 700;
    color: var(--gold);
}
.availability-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.4em 1em;
    border-radius: 20px;
}
.avail-ok { background: #16a08520; color: #1abc9c; border: 1px solid #16a08540; }
.avail-no { background: #2980b920; color: #3498db; border: 1px solid #2980b940; }
.avail-checking { background: #f39c1220; color: #f1c40f; border: 1px solid #f39c1240; }
.step-badge {
    width: 28px; height: 28px;
    background: rgba(201,168,76,0.15);
    color: var(--gold);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    margin-right: 0.5rem;
}
</style>
@endsection

@section('content')
<div class="booking-hero">
    <div class="container">
        <a href="{{ route('booking.index') }}" class="text-muted text-decoration-none mb-2 d-inline-block">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <h2 class="mb-1">Reservasi <span class="text-gold">{{ $table->name }}</span></h2>
        <p class="text-muted mb-0">Rp {{ number_format($table->getCurrentPricePerHour(), 0, ',', '.') }} / jam &bull; {{ $table->capacity }} pemain</p>
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
                        <span id="end-time-display" class="text-muted ms-2" style="font-size:0.85rem"></span>
                    </div>
                </div>

                <!-- Step 2: Personal Info -->
                <div class="form-card mb-4">
                    <h5 class="mb-4"><span class="step-badge">2</span>Data Pemesan</h5>

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
                                <span class="input-group-text" style="background:#0f0f1a;border-color:#2a2a45;color:#666">+62</span>
                                <input type="tel" name="customer_phone" id="customer_phone"
                                    class="form-control @error('customer_phone') is-invalid @enderror"
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
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-3" style="background:#0f0f1a;border:1px solid #2a2a45">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MidtransLogo.png/320px-MidtransLogo.png"
                             alt="Midtrans" height="24" style="filter:brightness(0.8)">
                        <div style="font-size:0.85rem;color:#888">
                            Dibayar via Midtrans — GoPay, OVO, DANA, Transfer Bank, Kartu Kredit
                        </div>
                    </div>
                    <button type="submit" id="submitBtn" class="btn btn-gold btn-lg w-100" disabled>
                        <i class="bi bi-lock-fill me-2"></i>Lanjut ke Pembayaran
                    </button>
                    <p class="text-center text-muted mt-2" style="font-size:0.78rem">
                        Jika meja sudah penuh, booking akan masuk <strong>Waiting List</strong> otomatis
                    </p>
                </div>
            </div>

            <!-- Right: Summary -->
            <div class="col-lg-4">
                <div class="summary-card">
                    <div class="text-center mb-3" style="font-size:3rem">🀄</div>
                    <h6 class="text-gold mb-3 text-center">Ringkasan Reservasi</h6>
                    <hr style="border-color:#2a2a45">
                    <div class="d-flex justify-content-between mb-2" style="font-size:0.875rem">
                        <span class="text-muted">Meja</span>
                        <span class="fw-500">{{ $table->name }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:0.875rem">
                        <span class="text-muted">Tanggal</span>
                        <span id="s-date">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:0.875rem">
                        <span class="text-muted">Waktu</span>
                        <span id="s-time">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:0.875rem">
                        <span class="text-muted">Durasi</span>
                        <span id="s-duration">—</span>
                    </div>
                    <hr style="border-color:#2a2a45">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Total</span>
                        <div class="price-display" id="s-total">—</div>
                    </div>
                    <div id="s-waiting-info" class="mt-3 p-2 rounded-2 text-center" style="background:#2980b920;color:#3498db;font-size:0.8rem;display:none!important">
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

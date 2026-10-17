@extends('layouts.dashboard')
@section('title', 'Buat Booking')
@section('page-title', 'Buat Booking Admin')

@section('head')
<style>
.line-row {
    background: rgba(58,20,20,0.05);
    border-radius: 16px;
    padding: 0.9rem 1rem;
    margin-bottom: 0.75rem;
}
.availability-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.3em 0.9em;
    border-radius: 20px;
}
.avail-ok { background: #DCEFDD; color: #1f7a34; }
.avail-no { background: #F6DADA; color: #9b2c2c; }
.avail-checking { background: #FBF0C8; color: #8a6d1a; }
.busy-chip {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.2em 0.7em;
    border-radius: 999px;
    margin: 0 0.3rem 0.3rem 0;
    background: rgba(58,20,20,0.1);
    color: var(--ink);
}
.busy-chip.pending { background: #FBF0C8; color: #8a6d1a; }
.source-option {
    border: 2px solid rgba(58,20,20,0.12);
    border-radius: 14px;
    padding: 0.8rem 1rem;
    cursor: pointer;
    height: 100%;
}
.btn-check:checked + .source-option { border-color: var(--red); background: rgba(85,20,20,0.06); }
</style>
@endsection

@section('content')
<form method="POST" action="{{ route('dashboard.bookings.store') }}" id="adminBookingForm">
    @csrf

    <div class="card-dark mb-4">
        <div class="card-header"><i class="bi bi-person-badge me-2 text-gold"></i>Jenis Booking</div>
        <div class="card-body">
            <div class="row g-3">
                @php
                    $sourceInfo = [
                        'owner' => ['bi-star-fill', 'Gratis (Rp 0). Meja menyala otomatis. Tercatat di laporan sebagai Rp 0.'],
                        'cash'  => ['bi-cash-coin', 'Pelanggan bayar tunai di kasir. Harga sesuai tarif meja, masuk pendapatan. Meja menyala otomatis.'],
                        'block' => ['bi-slash-circle', 'Menutup jadwal (misalnya perbaikan meja). Meja tidak dinyalakan dan tidak ada tagihan.'],
                    ];
                @endphp
                @foreach($sources as $value => $label)
                <div class="col-md-4">
                    <input type="radio" class="btn-check" name="source" id="source-{{ $value }}" value="{{ $value }}"
                        {{ old('source', 'owner') === $value ? 'checked' : '' }}>
                    <label class="source-option d-block" for="source-{{ $value }}">
                        <div style="font-weight:800"><i class="bi {{ $sourceInfo[$value][0] }} me-2" style="color:var(--red)"></i>{{ $label }}</div>
                        <div style="font-size:0.8rem;color:var(--ink-mute)" class="mt-1">{{ $sourceInfo[$value][1] }}</div>
                    </label>
                </div>
                @endforeach
            </div>
            @error('source')<div class="mt-2" style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="card-dark mb-4">
        <div class="card-header"><i class="bi bi-grid-3x3-gap me-2 text-gold"></i>Meja &amp; Waktu</div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="booking_date" id="booking_date" class="form-control"
                        min="{{ today()->toDateString() }}"
                        value="{{ old('booking_date', today()->toDateString()) }}" required>
                    @error('booking_date')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-8 d-flex align-items-end">
                    <button type="button" id="playNowBtn" class="btn btn-outline-secondary">
                        <i class="bi bi-lightning-charge-fill me-1"></i>Mulai Sekarang
                    </button>
                </div>
            </div>

            @include('booking.partials.lines', ['tables' => $tables, 'maxTables' => $maxTables])
        </div>
    </div>

    <div class="card-dark mb-4">
        <div class="card-header"><i class="bi bi-card-text me-2 text-gold"></i>Keterangan</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label">Nama (opsional)</label>
                    <input type="text" name="customer_name" class="form-control" maxlength="100"
                        value="{{ old('customer_name') }}" placeholder="Otomatis: Owner / Pelanggan Tunai / Jadwal Diblokir">
                    @error('customer_name')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-3">
                    <label class="form-label">No. HP (opsional)</label>
                    <input type="tel" name="customer_phone" id="customer_phone" class="form-control"
                        inputmode="numeric" maxlength="13" value="{{ old('customer_phone') }}" placeholder="08xxxxxxxxxx">
                    @error('customer_phone')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-5">
                    <label class="form-label">Catatan (opsional)</label>
                    <input type="text" name="notes" class="form-control" maxlength="255"
                        value="{{ old('notes') }}" placeholder="Misal: acara privat, perbaikan lampu meja">
                </div>
            </div>
        </div>
    </div>

    <div class="card-dark">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div style="font-size:0.8rem;color:var(--ink-mute)">Total</div>
                <div id="adminTotal" style="font-size:1.5rem;font-weight:800;color:var(--red)">Rp 0</div>
                <div id="adminHint" style="font-size:0.8rem;color:var(--ink-mute)"></div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('dashboard.bookings') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" id="adminSubmit" class="btn btn-gold" disabled>
                    <i class="bi bi-check2-circle me-1"></i>Simpan Booking
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
@include('booking.partials.lines-script')
<script>
const serverEpochMs = {{ now()->getTimestampMs() }};
const serverUtcOffsetMin = {{ now()->utcOffset() }};
const pageLoadedAt = Date.now();
const submitBtn = document.getElementById('adminSubmit');
const hint = document.getElementById('adminHint');
let lastLines = [];

function serverNow() {
    return new Date(serverEpochMs + (Date.now() - pageLoadedAt) + serverUtcOffsetMin * 60000);
}

function selectedSource() {
    return document.querySelector('input[name="source"]:checked')?.value || 'owner';
}

function render(lines) {
    lastLines = lines;
    const source = selectedSource();
    const total = source === 'cash' ? lines.reduce((sum, l) => sum + l.price, 0) : 0;
    document.getElementById('adminTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');

    const bad = lines.filter(l => l.state === 'bad').length;
    const waiting = lines.filter(l => l.state !== 'ok').length;

    submitBtn.disabled = waiting > 0;
    hint.textContent = bad > 0
        ? `${bad} meja bentrok. Ganti jam atau hapus meja yang ditandai merah.`
        : (waiting > 0 ? 'Isi jam mulai untuk setiap meja.' : `${lines.length} meja siap disimpan.`);
}

document.querySelectorAll('input[name="source"]').forEach(el => el.addEventListener('change', () => render(lastLines)));

window.initBookingLines({
    csrf: '{{ csrf_token() }}',
    dateInput: document.getElementById('booking_date'),
    playNowBtn: document.getElementById('playNowBtn'),
    serverNow,
    allowPast: true,
    onChange: render,
});

document.getElementById('customer_phone').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 13);
});
</script>
@endsection

@extends('layouts.dashboard')
@section('title', 'Voucher')
@section('page-title', 'Voucher')

@section('content')

<form method="POST" action="{{ route('dashboard.vouchers.store') }}" class="card-dark mb-4">
    @csrf
    <div class="card-header"><i class="bi bi-ticket-perforated-fill me-2 text-gold"></i>Generate Kode Voucher</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="v-label">Nama kolaborasi / keterangan</label>
                <input type="text" id="v-label" name="label" class="form-control" maxlength="60" required
                    value="{{ old('label', $label) }}" placeholder="Misal: Kolaborasi Kopi Senja">
                @error('label')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="v-count">Jumlah kode</label>
                <input type="number" id="v-count" name="count" class="form-control" min="1" max="{{ \App\Models\Voucher::MAX_PER_BATCH }}" required value="{{ old('count', 10) }}">
                @error('count')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="v-type">Jenis potongan</label>
                <select id="v-type" name="discount_type" class="form-select">
                    <option value="amount" @selected(old('discount_type', 'amount') === 'amount')>Nominal (Rp)</option>
                    <option value="percent" @selected(old('discount_type') === 'percent')>Persen (%)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="v-value" id="v-value-label">Besar potongan</label>
                <input type="number" id="v-value" name="discount_value" class="form-control" min="1" required value="{{ old('discount_value') }}" placeholder="Misal: 25000">
                @error('discount_value')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3" id="v-max-wrap">
                <label class="form-label" for="v-max">Potongan maksimal (Rp, opsional)</label>
                <input type="number" id="v-max" name="max_discount" class="form-control" min="1" value="{{ old('max_discount') }}" placeholder="Kosongkan jika tanpa batas">
                @error('max_discount')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="v-exp">Berlaku sampai (opsional)</label>
                <input type="date" id="v-exp" name="expires_on" class="form-control" min="{{ today()->toDateString() }}" value="{{ old('expires_on') }}">
                @error('expires_on')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
            </div>
        </div>
        <p class="mt-3 mb-3" style="font-size:0.8rem;color:var(--ink-mute)">Setiap kode berbeda dan hanya bisa dipakai satu kali untuk booking online. Kalau potongan menutup seluruh harga, booking langsung aktif tanpa QRIS.</p>
        <button type="submit" class="btn btn-gold"><i class="bi bi-magic me-1"></i>Generate</button>
    </div>
</form>

<div class="card-dark mb-4">
    <div class="card-header d-flex justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-list-ul me-2 text-gold"></i>Daftar Voucher</span>
        <span class="text-muted small">{{ $vouchers->count() }} kode</span>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-5">
                <select name="label" class="form-select" aria-label="Filter kolaborasi" onchange="this.form.submit()">
                    <option value="">Semua kolaborasi</option>
                    @foreach($labels as $item)
                    <option value="{{ $item }}" @selected($label === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select" aria-label="Filter status" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    @foreach(\App\Models\Voucher::STATUSES as $key => $text)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $text }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if($available->isNotEmpty())
        <label class="form-label" for="v-copy">Kode yang masih tersedia ({{ $available->count() }}), siap disalin untuk dikirim</label>
        <textarea id="v-copy" class="form-control mb-2" rows="4" readonly style="font-family:monospace;border-radius:12px">{{ $available->implode("\n") }}</textarea>
        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="v-copy-btn"><i class="bi bi-clipboard me-1"></i>Salin semua</button>
        @endif

        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Kolaborasi</th>
                        <th>Potongan</th>
                        <th>Berlaku sampai</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                    @php $state = $voucher->status(); @endphp
                    <tr>
                        <td class="fw-600" style="font-family:monospace">{{ $voucher->code }}</td>
                        <td style="font-size:0.875rem">{{ $voucher->label }}</td>
                        <td style="font-size:0.875rem">{{ $voucher->discount_label }}</td>
                        <td style="font-size:0.875rem">{{ $voucher->expires_on?->translatedFormat('d M Y') ?? 'Tanpa batas' }}</td>
                        <td style="font-size:0.8rem">
                            <span class="badge-s-{{ ['available' => 'active', 'held' => 'waiting', 'used' => 'done', 'expired' => 'cancelled'][$state] }}">{{ \App\Models\Voucher::STATUSES[$state] }}</span>
                            @if($voucher->order && in_array($state, ['used', 'held'], true))
                            <div class="mt-1" style="color:var(--ink-mute)">{{ $voucher->order->code }} · {{ $voucher->order->customer_name }}@if($voucher->used_at) · {{ $voucher->used_at->format('d/m H:i') }}@endif</div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(in_array($state, ['available', 'expired'], true))
                            <form method="POST" action="{{ route('dashboard.vouchers.destroy', $voucher) }}" onsubmit="return confirm('Hapus voucher {{ $voucher->code }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-secondary" style="color:var(--red)" title="Hapus" aria-label="Hapus voucher {{ $voucher->code }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @else
                            <span style="font-size:0.75rem;color:var(--ink-mute)">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4" style="color:var(--ink-mute)">Belum ada voucher.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
(() => {
    const type = document.getElementById('v-type');
    const value = document.getElementById('v-value');
    const maxWrap = document.getElementById('v-max-wrap');
    const sync = () => {
        const percent = type.value === 'percent';
        maxWrap.style.display = percent ? '' : 'none';
        if (percent) value.max = 100; else value.removeAttribute('max');
        value.placeholder = percent ? 'Misal: 20' : 'Misal: 25000';
        document.getElementById('v-value-label').textContent = percent ? 'Besar potongan (%)' : 'Besar potongan (Rp)';
    };
    type.addEventListener('change', sync);
    sync();

    const copyBtn = document.getElementById('v-copy-btn');
    copyBtn?.addEventListener('click', async () => {
        const area = document.getElementById('v-copy');
        try {
            await navigator.clipboard.writeText(area.value);
        } catch (e) {
            area.select();
            document.execCommand('copy');
        }
        copyBtn.innerHTML = '<i class="bi bi-check2 me-1"></i>Tersalin';
    });
})();
</script>
@endsection

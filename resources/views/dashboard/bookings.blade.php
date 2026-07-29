@extends('layouts.dashboard')
@section('title', 'Kelola Booking')
@section('page-title', 'Kelola Booking')

@section('content')

<!-- Filter Bar -->
<div class="card-dark mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-sm-3">
                <label class="form-label text-muted small">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / No HP / Kode..."
                    class="form-control" style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;">
            </div>
            <div class="col-sm-3">
                <label class="form-label text-muted small">Status</label>
                <select name="status" class="form-select" style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;">
                    <option value="">Semua Status</option>
                    <option value="pending_payment" {{ request('status') === 'pending_payment' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                    <option value="waiting"         {{ request('status') === 'waiting'         ? 'selected' : '' }}>Waiting List</option>
                    <option value="active"          {{ request('status') === 'active'          ? 'selected' : '' }}>Aktif</option>
                    <option value="done"            {{ request('status') === 'done'            ? 'selected' : '' }}>Selesai</option>
                    <option value="cancelled"       {{ request('status') === 'cancelled'       ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label text-muted small">Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}"
                    class="form-control" style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;">
            </div>
            <div class="col-sm-3">
                <button type="submit" class="btn btn-gold w-100">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card-dark">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-journal-check me-2 text-gold"></i>Daftar Booking</span>
        <span class="text-muted small">{{ $bookings->total() }} booking</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th>Meja</th>
                        <th>Tanggal & Waktu</th>
                        <th>Durasi</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $bk)
                    <tr>
                        <td>
                            <code style="color:var(--red);font-size:0.8rem">{{ $bk->booking_code }}</code>
                        </td>
                        <td>
                            <div style="font-weight:500">{{ $bk->customer_name }}</div>
                            <div style="font-size:0.75rem;color:var(--ink-mute)">{{ $bk->customer_phone }}</div>
                        </td>
                        <td style="font-size:0.875rem">{{ $bk->table->name }}</td>
                        <td style="font-size:0.875rem">
                            {{ $bk->booking_date->format('d/m/Y') }}<br>
                            <span style="color:var(--ink-mute);font-size:0.78rem">
                                {{ substr($bk->start_time, 0, 5) }} – {{ substr($bk->end_time, 0, 5) }}
                            </span>
                        </td>
                        <td style="font-size:0.875rem">{{ $bk->duration_hours }} jam</td>
                        <td style="color:var(--red);font-weight:700;font-size:0.875rem">
                            Rp {{ number_format($bk->total_price, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge-s-{{ $bk->status === 'pending_payment' ? 'pending' : $bk->status }}">
                                {{ $bk->status_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if(!in_array($bk->status, ['done', 'cancelled']))
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    Ubah
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="background:var(--cream);border:none;">
                                    @if($bk->status !== 'active')
                                    <li>
                                        <form method="POST" action="{{ route('dashboard.bookings.status', $bk) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="active">
                                            <button class="dropdown-item" style="color:#1f7a34">
                                                <i class="bi bi-play-circle me-2"></i>Aktifkan
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    <li>
                                        <form method="POST" action="{{ route('dashboard.bookings.status', $bk) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="done">
                                            <button class="dropdown-item" style="color:var(--ink-mute)">
                                                <i class="bi bi-check2-circle me-2"></i>Selesai (Cash)
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider" style="border-color:rgba(58,20,20,0.12)"></li>
                                    <li>
                                        <form method="POST" action="{{ route('dashboard.bookings.status', $bk) }}"
                                              onsubmit="return confirm('Batalkan booking ini?')">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="cancelled">
                                            <button class="dropdown-item" style="color:var(--red)">
                                                <i class="bi bi-x-circle me-2"></i>Batalkan
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                            @else
                            <span style="font-size:0.75rem;color:var(--ink-mute)">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Tidak ada booking ditemukan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($bookings->hasPages())
    <div class="card-body border-top" style="border-color:rgba(58,20,20,0.12)!important">
        {{ $bookings->links() }}
    </div>
    @endif
</div>

@endsection

@extends('layouts.dashboard')
@section('title', 'Kelola Meja')
@section('page-title', 'Kelola Meja')

@section('content')

<div class="d-flex justify-content-end mb-4">
    <a href="{{ route('dashboard.tables.create') }}" class="btn btn-gold">
        <i class="bi bi-plus-lg me-1"></i>Tambah Meja
    </a>
</div>

<div class="card-dark">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-grid-3x3-gap-fill me-2 text-gold"></i>Daftar Meja</span>
        <span class="text-muted small">{{ $tables->count() }} meja</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th class="text-center">Kapasitas</th>
                        <th>Status</th>
                        <th class="text-end">Harga / Jam</th>
                        <th class="text-center">Riwayat Booking</th>
                        <th class="text-center">Perangkat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tables as $table)
                    <tr>
                        <td>
                            <div class="fw-600">{{ $table->name }}</div>
                            @if($table->description)
                            <div style="font-size:0.75rem;color:var(--ink-mute)">{{ $table->description }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $table->capacity }} pemain</td>
                        <td>
                            <span class="badge-s-{{ $table->status === 'available' ? 'active' : ($table->status === 'occupied' ? 'cancelled' : 'waiting') }}">
                                {{ ucfirst($table->status) }}
                            </span>
                        </td>
                        <td class="text-end fw-700" style="color:var(--red)">
                            Rp {{ number_format($table->pricing?->price_per_hour ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-center">{{ $table->bookings_count }}</td>
                        <td class="text-center">
                            @if($table->esp32_meja_id)
                            <span class="badge-s-active" title="ID Meja ESP32: {{ $table->esp32_meja_id }}" style="font-family:monospace;">
                                <i class="bi bi-cpu me-1"></i>Meja {{ $table->esp32_meja_id }}
                            </span>
                            @else
                            <span style="font-size:0.75rem;color:var(--ink-mute)">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="{{ route('dashboard.tables.edit', $table) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('dashboard.tables.destroy', $table) }}"
                                      onsubmit="return confirm('Hapus meja {{ $table->name }}? Tindakan ini tidak bisa dibatalkan.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" style="color:var(--red)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada meja
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

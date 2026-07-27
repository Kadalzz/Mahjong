@extends('layouts.dashboard')
@section('title', 'Edit Harga')
@section('page-title', 'Kelola Harga Meja')

@section('content')

<div class="row g-4">
    @foreach($tables as $table)
    <div class="col-md-6 col-xl-4">
        <div class="card-dark">
            <div class="card-header d-flex align-items-center gap-2">
                <span style="font-size:1.2rem">🀄</span>
                {{ $table->name }}
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small mb-1">Harga Saat Ini</div>
                    <div style="font-size:1.75rem;font-weight:700;color:var(--gold)">
                        Rp {{ number_format($table->pricing?->price_per_hour ?? 0, 0, ',', '.') }}
                        <span style="font-size:0.9rem;font-weight:400;color:#666">/ jam</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('dashboard.pricing.update', $table) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label text-muted small">Harga Baru (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:#0f0f1a;border-color:#2a2a45;color:#666">Rp</span>
                            <input type="number" name="price_per_hour"
                                class="form-control"
                                style="background:#0f0f1a;border-color:#2a2a45;color:#e0e0e0"
                                value="{{ $table->pricing?->price_per_hour ?? 0 }}"
                                min="0" step="1000" required>
                        </div>
                    </div>
                    <div class="mb-3" style="font-size:0.8rem;color:#555">
                        <i class="bi bi-info-circle me-1"></i>
                        Kapasitas: {{ $table->capacity }} pemain &bull;
                        Status:
                        <span style="color:{{ $table->status === 'available' ? '#1abc9c' : '#e74c3c' }}">
                            {{ ucfirst($table->status) }}
                        </span>
                    </div>
                    <button type="submit" class="btn btn-gold btn-sm w-100">
                        <i class="bi bi-check-lg me-1"></i>Simpan Harga
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection

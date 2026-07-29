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
                    <div style="font-size:1.75rem;font-weight:700;color:var(--red)">
                        Rp {{ number_format($table->pricing?->price_per_hour ?? 0, 0, ',', '.') }}
                        <span style="font-size:0.9rem;font-weight:400;color:var(--ink-mute)">/ jam</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('dashboard.pricing.update', $table) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label small" style="color:var(--ink-mute)">Harga Baru (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:rgba(58,20,20,0.06);border:none;color:var(--ink-mute);border-radius:999px 0 0 999px;">Rp</span>
                            <input type="number" name="price_per_hour"
                                class="form-control"
                                style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:0 999px 999px 0;"
                                value="{{ $table->pricing?->price_per_hour ?? 0 }}"
                                min="0" step="1000" required>
                        </div>
                    </div>
                    <div class="mb-3" style="font-size:0.8rem;color:var(--ink-mute)">
                        <i class="bi bi-info-circle me-1"></i>
                        Kapasitas: {{ $table->capacity }} pemain &bull;
                        Status:
                        <span style="color:{{ $table->status === 'available' ? '#1f7a34' : '#551414' }}">
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

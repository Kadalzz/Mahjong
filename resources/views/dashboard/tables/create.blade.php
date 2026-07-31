@extends('layouts.dashboard')
@section('title', 'Tambah Meja')
@section('page-title', 'Tambah Meja')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card-dark">
            <div class="card-header">
                <i class="bi bi-plus-lg me-2 text-gold"></i>Meja Baru
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dashboard.tables.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small" style="color:var(--ink-mute)">Nama Meja</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            class="form-control @error('name') is-invalid @enderror"
                            style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;"
                            placeholder="Meja 7 - Reguler" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small" style="color:var(--ink-mute)">Kapasitas</label>
                            <input type="number" name="capacity" value="{{ old('capacity', 4) }}" min="1" max="20"
                                class="form-control @error('capacity') is-invalid @enderror"
                                style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;"
                                required>
                            @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small" style="color:var(--ink-mute)">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror"
                                style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;">
                                <option value="available" {{ old('status') === 'available' ? 'selected' : '' }}>Available</option>
                                <option value="occupied" {{ old('status') === 'occupied' ? 'selected' : '' }}>Occupied</option>
                                <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small" style="color:var(--ink-mute)">Harga per Jam (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:rgba(58,20,20,0.06);border:none;color:var(--ink-mute);border-radius:999px 0 0 999px;">Rp</span>
                            <input type="number" name="price_per_hour" value="{{ old('price_per_hour', 50000) }}" min="0" step="1000"
                                class="form-control @error('price_per_hour') is-invalid @enderror"
                                style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:0 999px 999px 0;"
                                required>
                        </div>
                        @error('price_per_hour')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label small" style="color:var(--ink-mute)">Deskripsi (opsional)</label>
                        <textarea name="description" rows="2"
                            class="form-control @error('description') is-invalid @enderror"
                            style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:16px;"
                            placeholder="Meja Mahjong standar nyaman">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-gold flex-grow-1">
                            <i class="bi bi-check-lg me-1"></i>Simpan Meja
                        </button>
                        <a href="{{ route('dashboard.tables.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

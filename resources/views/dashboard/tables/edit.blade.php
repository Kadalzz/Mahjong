@extends('layouts.dashboard')
@section('title', 'Edit Meja')
@section('page-title', 'Edit Meja')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card-dark">
            <div class="card-header">
                <i class="bi bi-pencil me-2 text-gold"></i>{{ $table->name }}
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dashboard.tables.update', $table) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small" style="color:var(--ink-mute)">Nama Meja</label>
                        <input type="text" name="name" value="{{ old('name', $table->name) }}"
                            class="form-control @error('name') is-invalid @enderror"
                            style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;"
                            required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small" style="color:var(--ink-mute)">Kapasitas</label>
                            <input type="number" name="capacity" value="{{ old('capacity', $table->capacity) }}" min="1" max="20"
                                class="form-control @error('capacity') is-invalid @enderror"
                                style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;"
                                required>
                            @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small" style="color:var(--ink-mute)">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror"
                                style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;">
                                @foreach(['available' => 'Available', 'occupied' => 'Occupied', 'maintenance' => 'Maintenance'] as $val => $label)
                                <option value="{{ $val }}" {{ old('status', $table->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small" style="color:var(--ink-mute)">Deskripsi (opsional)</label>
                        <textarea name="description" rows="2"
                            class="form-control @error('description') is-invalid @enderror"
                            style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:16px;">{{ old('description', $table->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small" style="color:var(--ink-mute)">ID Meja ESP32 (opsional)</label>
                        <input type="number" name="esp32_meja_id" value="{{ old('esp32_meja_id', $table->esp32_meja_id) }}" min="1" max="255"
                            class="form-control @error('esp32_meja_id') is-invalid @enderror"
                            style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;font-family:monospace;"
                            placeholder="1">
                        @error('esp32_meja_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div style="font-size:0.75rem;color:var(--ink-mute);margin-top:0.35rem;">
                            Nomor ID meja ini di firmware ESP32 Master (harus sama dengan urutan <code>clientMAC[]</code> di master.ino). Perintah dikirim lewat bridge WebSocket saat pembayaran berhasil.
                        </div>
                    </div>

                    <div class="mb-4" style="font-size:0.8rem;color:var(--ink-mute)">
                        <i class="bi bi-info-circle me-1"></i>
                        Harga per jam meja ini dikelola terpisah di halaman
                        <a href="{{ route('dashboard.pricing') }}" style="color:var(--red);font-weight:600;">Kelola Harga</a>.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-gold flex-grow-1">
                            <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
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

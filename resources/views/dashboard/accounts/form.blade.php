@extends('layouts.dashboard')
@php $editing = $account->exists; @endphp
@section('title', $editing ? 'Ubah Akun' : 'Buat Akun Staf')
@section('page-title', $editing ? 'Ubah Akun Staf' : 'Buat Akun Staf')

@section('content')
<form method="POST" action="{{ $editing ? route('dashboard.accounts.update', $account) : route('dashboard.accounts.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="card-dark mb-4">
        <div class="card-header"><i class="bi bi-person-badge me-2 text-gold"></i>Data Akun</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" maxlength="100" required
                        value="{{ old('name', $account->name) }}" placeholder="Misal: Tablet Bar atau Kasir Budi">
                    @error('name')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email (untuk login)</label>
                    <input type="email" name="email" class="form-control" maxlength="150" required
                        value="{{ old('email', $account->email) }}" autocomplete="off">
                    @error('email')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $editing ? 'Password baru (kosongkan jika tidak diubah)' : 'Password awal' }}</label>
                    <input type="text" name="password" class="form-control" minlength="8" maxlength="100"
                        {{ $editing ? '' : 'required' }} autocomplete="new-password" placeholder="Minimal 8 karakter">
                    @error('password')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
                    <div style="font-size:0.75rem;color:var(--ink-mute)" class="mt-1">Karyawan tidak bisa mengganti password sendiri. Catat dan berikan password ini langsung.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-dark mb-4">
        <div class="card-header"><i class="bi bi-ui-checks-grid me-2 text-gold"></i>Fitur yang boleh dipakai</div>
        <div class="card-body">
            @php $selected = old('permissions', $account->permissions ?? []); @endphp
            <div class="row g-2">
                @foreach($permissions as $key => $label)
                <div class="col-md-6">
                    <div class="form-check" style="background:rgba(58,20,20,0.05);border-radius:12px;padding:0.7rem 0.7rem 0.7rem 2.2rem">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}"
                            id="perm-{{ $key }}" {{ in_array($key, $selected, true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="perm-{{ $key }}" style="font-weight:600">{{ $label }}</label>
                    </div>
                </div>
                @endforeach
            </div>
            @error('permissions.*')<div style="color:#9b2c2c;font-size:0.85rem" class="mt-2">{{ $message }}</div>@enderror
            <div style="font-size:0.8rem;color:var(--ink-mute)" class="mt-3">
                Untuk tablet bar, centang <strong>Jeda meja 2/5/10 menit</strong> saja. Pengaturan akun ini hanya bisa diubah oleh admin utama.
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('dashboard.accounts.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-gold"><i class="bi bi-check2-circle me-1"></i>{{ $editing ? 'Simpan Perubahan' : 'Buat Akun' }}</button>
    </div>
</form>
@endsection

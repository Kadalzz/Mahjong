@extends('layouts.dashboard')
@section('title', 'Reset Password Pemain')
@section('page-title', 'Reset Password Pemain')

@section('content')
<form method="POST" action="{{ route('dashboard.players.update', $player) }}">
    @csrf
    @method('PUT')

    <div class="card-dark mb-4">
        <div class="card-header"><i class="bi bi-key me-2 text-gold"></i>{{ $player->nickname }} ({{ $player->email }})</div>
        <div class="card-body">
            <label class="form-label">Password baru</label>
            <input type="text" name="password" class="form-control" minlength="8" maxlength="100" required autocomplete="new-password" placeholder="Minimal 8 karakter, huruf, angka, dan huruf besar">
            @error('password')<div style="color:#9b2c2c;font-size:0.85rem">{{ $message }}</div>@enderror
            <div style="font-size:0.75rem;color:var(--ink-mute)" class="mt-1">Pemain akan otomatis keluar dari semua perangkat. Berikan password ini langsung kepada pemain.</div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-gold">Simpan</button>
        <a href="{{ route('dashboard.players.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection

@extends('layouts.dashboard')
@section('title', 'Akun Pemain')
@section('page-title', 'Akun Pemain')

@section('content')

<form method="GET" action="{{ route('dashboard.players.index') }}" class="d-flex gap-2 mb-3">
    <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Cari nama pemain atau email">
    <button type="submit" class="btn btn-gold"><i class="bi bi-search"></i></button>
</form>

<div class="card-dark">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-controller me-2 text-gold"></i>Daftar Pemain</span>
        <span class="text-muted small">{{ $players->total() }} pemain</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>Nama Pemain</th>
                        <th>Email</th>
                        <th>Terdaftar</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($players as $player)
                    <tr>
                        <td class="fw-600">{{ $player->nickname }}</td>
                        <td style="font-size:0.875rem">{{ $player->email }}</td>
                        <td style="font-size:0.875rem">{{ $player->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <a href="{{ route('dashboard.players.edit', $player) }}" class="btn btn-sm btn-outline-secondary" title="Reset password">
                                <i class="bi bi-key"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-4" style="color:var(--ink-mute)">Belum ada pemain.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $players->links() }}</div>
@endsection

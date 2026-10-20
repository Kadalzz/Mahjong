@extends('layouts.dashboard')
@section('title', 'Kelola Akun')
@section('page-title', 'Kelola Akun')

@section('content')

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('dashboard.accounts.create') }}" class="btn btn-gold">
        <i class="bi bi-person-plus-fill me-1"></i>Buat Akun Staf
    </a>
</div>

<div class="card-dark">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-people-fill me-2 text-gold"></i>Daftar Akun</span>
        <span class="text-muted small">{{ $users->count() }} akun</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Fitur yang boleh dipakai</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $account)
                    <tr>
                        <td class="fw-600">{{ $account->name }}</td>
                        <td style="font-size:0.875rem">{{ $account->email }}</td>
                        <td style="font-size:0.8rem;max-width:380px">
                            @if($account->isAdmin())
                                <span class="badge-s-active">Admin utama — semua fitur</span>
                            @elseif(empty($account->permissions))
                                <span style="color:var(--ink-mute)">Belum ada fitur</span>
                            @else
                                @foreach($account->permissions as $key)
                                <span class="badge-s-waiting me-1 mb-1" style="font-size:0.7rem">{{ \App\Models\User::PERMISSIONS[$key] ?? $key }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            <span class="badge-s-{{ $account->is_active ? 'active' : 'cancelled' }}">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td class="text-center">
                            @if(!$account->isAdmin())
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="{{ route('dashboard.accounts.edit', $account) }}" class="btn btn-sm btn-outline-secondary" title="Ubah fitur / reset password">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('dashboard.accounts.toggle', $account) }}"
                                      onsubmit="return confirm('{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }} akun {{ $account->name }}?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" style="color:{{ $account->is_active ? 'var(--red)' : '#1f7a34' }}" title="{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i class="bi bi-{{ $account->is_active ? 'person-slash' : 'person-check' }}"></i>
                                    </button>
                                </form>
                            </div>
                            @else
                            <span style="font-size:0.75rem;color:var(--ink-mute)">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

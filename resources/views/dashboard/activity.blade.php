@extends('layouts.dashboard')
@section('title', 'Catatan Aktivitas')
@section('page-title', 'Catatan Aktivitas')

@section('content')

<div class="card-dark mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-sm-4">
                <label class="form-label text-muted small">Jenis aktivitas</label>
                <select name="action" class="form-select" style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;">
                    <option value="">Semua</option>
                    @foreach($actions as $key => $label)
                    <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-gold w-100"><i class="bi bi-search me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card-dark">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-clock-history me-2 text-gold"></i>Riwayat</span>
        <span class="text-muted small">{{ $logs->total() }} catatan</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Siapa</th>
                        <th>Aktivitas</th>
                        <th>Meja</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td style="font-size:0.85rem">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="fw-600">{{ $log->user_name }}</td>
                        <td style="font-size:0.875rem">{{ $log->action_label }}</td>
                        <td style="font-size:0.875rem">{{ $log->table_name ?? '—' }}</td>
                        <td style="font-size:0.85rem">
                            @if($log->minutes){{ $log->minutes }} menit @endif
                            {{ $log->detail }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada aktivitas
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-body border-top" style="border-color:rgba(58,20,20,0.12)!important">
        {{ $logs->links() }}
    </div>
    @endif
</div>

@endsection

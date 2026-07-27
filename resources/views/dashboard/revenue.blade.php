@extends('layouts.dashboard')
@section('title', 'Revenue')
@section('page-title', 'Laporan Revenue')

@section('content')

<!-- Filter -->
<div class="card-dark mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-sm-4">
                <label class="form-label text-muted small">Dari Tanggal</label>
                <input type="date" name="from" value="{{ $from }}"
                    class="form-control" style="background:#0f0f1a;border-color:#2a2a45;color:#e0e0e0">
            </div>
            <div class="col-sm-4">
                <label class="form-label text-muted small">Sampai Tanggal</label>
                <input type="date" name="to" value="{{ $to }}"
                    class="form-control" style="background:#0f0f1a;border-color:#2a2a45;color:#e0e0e0">
            </div>
            <div class="col-sm-4">
                <button type="submit" class="btn btn-gold w-100">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value text-gold mt-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-label">Jumlah Transaksi</div>
            <div class="stat-value mt-1">{{ $transactions->count() }}</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-label">Rata-rata Transaksi</div>
            <div class="stat-value mt-1">
                Rp {{ $transactions->count() ? number_format($totalRevenue / $transactions->count(), 0, ',', '.') : 0 }}
            </div>
        </div>
    </div>
</div>

<!-- Chart -->
@if($chartData->isNotEmpty())
<div class="card-dark mb-4">
    <div class="card-header"><i class="bi bi-graph-up-arrow me-2 text-gold"></i>Revenue per Hari</div>
    <div class="card-body">
        <canvas id="revenueChart" height="120"></canvas>
    </div>
</div>
@endif

<!-- Table -->
<div class="card-dark">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-receipt me-2 text-gold"></i>Detail Transaksi</span>
        <span class="text-muted small">{{ $transactions->count() }} transaksi</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Kode Booking</th>
                        <th>Pelanggan</th>
                        <th>Meja</th>
                        <th>Metode</th>
                        <th class="text-end">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr>
                        <td>
                            <div style="font-size:0.875rem">{{ $tx->paid_at->format('d/m/Y') }}</div>
                            <div style="font-size:0.75rem;color:#666">{{ $tx->paid_at->format('H:i') }}</div>
                        </td>
                        <td>
                            <code style="color:var(--gold);font-size:0.8rem">{{ $tx->booking->booking_code }}</code>
                        </td>
                        <td>{{ $tx->booking->customer_name }}</td>
                        <td>{{ $tx->booking->table->name }}</td>
                        <td>
                            <span style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px">
                                {{ $tx->payment_method ?? '—' }}
                            </span>
                        </td>
                        <td class="text-end fw-600" style="color:var(--gold)">
                            Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi di periode ini</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($transactions->isNotEmpty())
                <tfoot>
                    <tr style="border-top:2px solid #2a2a45">
                        <td colspan="5" class="fw-600">Total</td>
                        <td class="text-end fw-700" style="color:var(--gold);font-size:1.1rem">
                            Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@if($chartData->isNotEmpty())
<script>
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($chartData->keys()) !!},
        datasets: [{
            label: 'Revenue',
            data: {!! json_encode($chartData->values()) !!},
            borderColor: 'rgba(201,168,76,0.8)',
            backgroundColor: 'rgba(201,168,76,0.1)',
            borderWidth: 2,
            pointRadius: 4,
            pointBackgroundColor: 'rgba(201,168,76,1)',
            tension: 0.4,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: c => 'Rp ' + c.raw.toLocaleString('id-ID') } }
        },
        scales: {
            x: { grid: { color: '#2a2a4530' } },
            y: { grid: { color: '#2a2a4530' }, ticks: { callback: v => 'Rp ' + (v/1000).toFixed(0) + 'k' } }
        }
    }
});
</script>
@endif
@endsection

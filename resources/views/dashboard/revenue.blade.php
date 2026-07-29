@extends('layouts.dashboard')
@section('title', 'Revenue')
@section('page-title', 'Laporan Revenue')

@section('content')

<!-- View toggle -->
<div class="d-flex gap-2 mb-4">
    <a href="{{ route('dashboard.revenue', ['view' => 'harian']) }}"
       class="btn btn-sm {{ $view === 'harian' ? 'btn-gold' : 'btn-outline-secondary' }}">
        <i class="bi bi-calendar-day me-1"></i>Harian
    </a>
    <a href="{{ route('dashboard.revenue', ['view' => 'bulanan']) }}"
       class="btn btn-sm {{ $view === 'bulanan' ? 'btn-gold' : 'btn-outline-secondary' }}">
        <i class="bi bi-calendar-month me-1"></i>Bulanan
    </a>
</div>

@if($view === 'harian')
<!-- Filter (Harian) -->
<div class="card-dark mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="harian">
            <div class="col-sm-4">
                <label class="form-label small" style="color:var(--ink-mute)">Dari Tanggal</label>
                <input type="date" name="from" value="{{ $from }}"
                    class="form-control" style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;">
            </div>
            <div class="col-sm-4">
                <label class="form-label small" style="color:var(--ink-mute)">Sampai Tanggal</label>
                <input type="date" name="to" value="{{ $to }}"
                    class="form-control" style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;">
            </div>
            <div class="col-sm-4">
                <button type="submit" class="btn btn-gold w-100">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>
@else
<!-- Filter (Bulanan) -->
<div class="card-dark mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="bulanan">
            <div class="col-sm-4">
                <label class="form-label small" style="color:var(--ink-mute)">Tahun</label>
                <select name="year" class="form-select" style="background:rgba(58,20,20,0.06);border:none;color:var(--ink);border-radius:999px;">
                    @foreach($years as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4">
                <button type="submit" class="btn btn-gold w-100">
                    <i class="bi bi-search me-1"></i>Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>
@endif

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

@if($view === 'harian')
<!-- Chart (Harian) -->
@if($chartData->isNotEmpty())
<div class="card-dark mb-4">
    <div class="card-header"><i class="bi bi-graph-up-arrow me-2 text-gold"></i>Revenue per Hari</div>
    <div class="card-body">
        <canvas id="revenueChartDaily" height="120"></canvas>
    </div>
</div>
@endif
@else
<!-- Chart (Bulanan) -->
<div class="card-dark mb-4">
    <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-gold"></i>Revenue per Bulan — {{ $year }}</div>
    <div class="card-body">
        <canvas id="revenueChartMonthly" height="120"></canvas>
    </div>
</div>

<!-- Monthly breakdown table -->
<div class="card-dark mb-4">
    <div class="card-header"><i class="bi bi-table me-2 text-gold"></i>Ringkasan per Bulan</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th class="text-center">Jumlah Transaksi</th>
                        <th class="text-end">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthlyData as $row)
                    <tr>
                        <td class="fw-600">{{ $row['month'] }}</td>
                        <td class="text-center">{{ $row['count'] }}</td>
                        <td class="text-end fw-700" style="color:var(--red)">
                            Rp {{ number_format($row['total'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="border-top:2px solid rgba(58,20,20,0.15)">
                        <td colspan="2" class="fw-700">Total {{ $year }}</td>
                        <td class="text-end fw-700" style="color:var(--red);font-size:1.1rem">
                            Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
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
                            <div style="font-size:0.75rem;color:var(--ink-mute)">{{ $tx->paid_at->format('H:i') }}</div>
                        </td>
                        <td>
                            <code style="color:var(--red);font-size:0.8rem">{{ $tx->booking->booking_code }}</code>
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
                    <tr style="border-top:2px solid rgba(58,20,20,0.15)">
                        <td colspan="5" class="fw-700">Total</td>
                        <td class="text-end fw-700" style="color:var(--red);font-size:1.1rem">
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
@if($view === 'harian')
    @if($chartData->isNotEmpty())
    <script>
    new Chart(document.getElementById('revenueChartDaily'), {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData->keys()) !!},
            datasets: [{
                label: 'Revenue',
                data: {!! json_encode($chartData->values()) !!},
                borderColor: 'rgba(85,20,20,0.85)',
                backgroundColor: 'rgba(85,20,20,0.1)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: 'rgba(85,20,20,1)',
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
                x: { grid: { color: 'rgba(58,20,20,0.08)' } },
                y: { grid: { color: 'rgba(58,20,20,0.08)' }, ticks: { callback: v => 'Rp ' + (v/1000).toFixed(0) + 'k' } }
            }
        }
    });
    </script>
    @endif
@else
    <script>
    const monthLabels = {!! json_encode($monthlyData->pluck('month')) !!};
    const monthTotals = {!! json_encode($monthlyData->pluck('total')) !!};

    new Chart(document.getElementById('revenueChartMonthly'), {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Revenue',
                data: monthTotals,
                backgroundColor: 'rgba(85,20,20,0.25)',
                borderColor: 'rgba(85,20,20,0.85)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: c => 'Rp ' + c.raw.toLocaleString('id-ID') } }
            },
            scales: {
                x: { grid: { color: 'rgba(58,20,20,0.08)' } },
                y: { grid: { color: 'rgba(58,20,20,0.08)' }, ticks: { callback: v => 'Rp ' + (v/1000).toFixed(0) + 'k' }, beginAtZero: true }
            }
        }
    });
    </script>
@endif
@endsection

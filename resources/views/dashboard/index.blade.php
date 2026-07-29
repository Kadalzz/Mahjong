@extends('layouts.dashboard')

@section('title', 'Overview Dashboard')
@section('page-title', 'Overview')

@section('content')

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Revenue Hari Ini</div>
                    <div class="stat-value mt-1">Rp {{ number_format($revenueToday, 0, ',', '.') }}</div>
                </div>
                <div class="stat-icon" style="background:#DCEFDD;color:#1f7a34">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Revenue Bulan Ini</div>
                    <div class="stat-value mt-1">Rp {{ number_format($revenueMonth, 0, ',', '.') }}</div>
                </div>
                <div class="stat-icon" style="background:rgba(85,20,20,0.12);color:var(--red)">
                    <i class="bi bi-graph-up"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Occupancy Rate</div>
                    <div class="stat-value mt-1">{{ $occupancyRate }}%</div>
                </div>
                <div class="stat-icon" style="background:#2980b920;color:#3498db">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="progress" style="height:4px;background:rgba(58,20,20,0.1)">
                    <div class="progress-bar" style="width:{{ $occupancyRate }}%;background:#2e6fba"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Waiting List</div>
                    <div class="stat-value mt-1">{{ $waitingCount }}</div>
                </div>
                <div class="stat-icon" style="background:#FBF0C8;color:#8a6d1a">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart + Recent Bookings -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-dark">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up-arrow me-2 text-gold"></i>Revenue 7 Hari Terakhir</span>
                <a href="{{ route('dashboard.revenue') }}" class="btn btn-sm btn-outline-secondary">Lihat Detail</a>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card-dark" style="height:100%">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-check me-2 text-gold"></i>Booking Terbaru</span>
                <a href="{{ route('dashboard.bookings') }}" class="btn btn-sm btn-outline-secondary">Semua</a>
            </div>
            <div class="card-body p-0" style="max-height:320px;overflow-y:auto">
                @forelse($recentBookings as $bk)
                <div class="d-flex align-items-center gap-3 px-4 py-3" style="border-bottom:1px solid rgba(58,20,20,0.08)">
                    <div class="flex-grow-1" style="min-width:0">
                        <div style="font-weight:700;font-size:0.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $bk->customer_name }}
                        </div>
                        <div style="font-size:0.75rem;color:var(--ink-mute)">{{ $bk->table->name }} &bull; {{ $bk->booking_date->format('d/m') }}</div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div style="font-size:0.8rem;color:var(--red);font-weight:700">
                            Rp {{ number_format($bk->total_price, 0, ',', '.') }}
                        </div>
                        <span class="badge-s-{{ $bk->status === 'pending_payment' ? 'pending' : $bk->status }}">
                            {{ $bk->status_label }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted">Belum ada booking</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const labels = {!! json_encode($last7Days->pluck('date')) !!};
const revenues = {!! json_encode($last7Days->pluck('revenue')) !!};

const ctx = document.getElementById('revenueChart').getContext('2d');
Chart.defaults.color = '#6b5d4a';

new Chart(ctx, {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Revenue (Rp)',
            data: revenues,
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
            tooltip: {
                callbacks: {
                    label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            x: { grid: { color: 'rgba(58,20,20,0.08)' }, ticks: { font: { size: 11 } } },
            y: {
                grid: { color: 'rgba(58,20,20,0.08)' },
                ticks: {
                    callback: v => 'Rp ' + (v/1000).toFixed(0) + 'k',
                    font: { size: 11 }
                }
            }
        }
    }
});
</script>
@endsection

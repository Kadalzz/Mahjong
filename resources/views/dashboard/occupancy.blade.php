@extends('layouts.dashboard')
@section('title', 'Occupancy')
@section('page-title', 'Laporan Occupancy')

@section('content')

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

<!-- Chart -->
<div class="card-dark mb-4">
    <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-gold"></i>Jam Terpakai per Meja</div>
    <div class="card-body">
        <canvas id="occupancyChart" height="140"></canvas>
    </div>
</div>

<!-- Summary Table -->
<div class="card-dark">
    <div class="card-header"><i class="bi bi-table me-2 text-gold"></i>Ringkasan per Meja</div>
    <div class="card-body p-0">
        <table class="table table-dark-custom mb-0">
            <thead>
                <tr>
                    <th>Meja</th>
                    <th class="text-center">Total Booking</th>
                    <th class="text-center">Total Jam</th>
                    <th>Occupancy Bar</th>
                </tr>
            </thead>
            <tbody>
                @php $maxHours = $tableSummary->max('total_hours') ?: 1; @endphp
                @foreach($tableSummary as $row)
                <tr>
                    <td class="fw-500">{{ $row['name'] }}</td>
                    <td class="text-center">{{ $row['total_bookings'] }}</td>
                    <td class="text-center">{{ $row['total_hours'] }} jam</td>
                    <td style="min-width:200px">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:8px;background:#2a2a45">
                                <div class="progress-bar" style="width:{{ $maxHours > 0 ? round(($row['total_hours']/$maxHours)*100) : 0 }}%;background:linear-gradient(90deg,rgba(201,168,76,0.6),rgba(201,168,76,1))"></div>
                            </div>
                            <span style="font-size:0.75rem;color:#888;width:40px;text-align:right">
                                {{ $maxHours > 0 ? round(($row['total_hours']/$maxHours)*100) : 0 }}%
                            </span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('scripts')
<script>
const tableNames = {!! json_encode($tables->pluck('name')) !!};
const dates = {!! json_encode($dates) !!};
const occupancyData = {!! json_encode($occupancyData) !!};

const colors = [
    'rgba(201,168,76,0.7)',
    'rgba(52,152,219,0.7)',
    'rgba(26,188,156,0.7)',
    'rgba(231,76,60,0.7)',
    'rgba(155,89,182,0.7)',
    'rgba(241,196,15,0.7)',
];

const datasets = tableNames.map((name, i) => ({
    label: name,
    data: occupancyData[name] || [],
    backgroundColor: colors[i % colors.length],
    borderColor: colors[i % colors.length].replace('0.7', '1'),
    borderWidth: 1,
    borderRadius: 4,
}));

new Chart(document.getElementById('occupancyChart'), {
    type: 'bar',
    data: { labels: dates, datasets },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: { color: '#888', font: { size: 11 } }
            },
            tooltip: { callbacks: { label: c => c.dataset.label + ': ' + c.raw + ' jam' } }
        },
        scales: {
            x: { grid: { color: '#2a2a4530' }, stacked: false },
            y: {
                grid: { color: '#2a2a4530' },
                ticks: { callback: v => v + ' jam' },
                beginAtZero: true
            }
        }
    }
});
</script>
@endsection

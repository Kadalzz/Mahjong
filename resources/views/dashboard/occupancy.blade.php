@extends('layouts.dashboard')
@section('title', 'Occupancy')
@section('page-title', 'Laporan Occupancy')

@section('content')

<div class="card-dark mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
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
                            <div class="progress flex-grow-1" style="height:8px;background:rgba(58,20,20,0.1)">
                                <div class="progress-bar" style="width:{{ $maxHours > 0 ? round(($row['total_hours']/$maxHours)*100) : 0 }}%;background:var(--red)"></div>
                            </div>
                            <span style="font-size:0.75rem;color:var(--ink-mute);width:40px;text-align:right">
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
    'rgba(85,20,20,0.75)',
    'rgba(46,111,186,0.75)',
    'rgba(47,158,68,0.75)',
    'rgba(212,160,23,0.75)',
    'rgba(122,32,32,0.75)',
    'rgba(139,90,43,0.75)',
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
                labels: { color: '#6b5d4a', font: { size: 11 } }
            },
            tooltip: { callbacks: { label: c => c.dataset.label + ': ' + c.raw + ' jam' } }
        },
        scales: {
            x: { grid: { color: 'rgba(58,20,20,0.08)' }, stacked: false },
            y: {
                grid: { color: 'rgba(58,20,20,0.08)' },
                ticks: { callback: v => v + ' jam' },
                beginAtZero: true
            }
        }
    }
});
</script>
@endsection

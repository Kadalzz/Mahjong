@extends('layouts.dashboard')
@section('title', 'Jeda Meja')
@section('page-title', 'Jeda Meja')

@section('head')
<style>
.jeda-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.jeda-card {
    background: var(--cream);
    border-radius: 20px;
    padding: 1.25rem;
    color: var(--ink);
    border: 3px solid transparent;
}
.jeda-card.is-paused { border-color: #2f9e44; background: #E6F3E7; }
.jeda-card.is-blocked { opacity: 0.85; }
.jeda-name { font-family: 'Baloo 2', sans-serif; font-weight: 800; font-size: 1.25rem; text-transform: uppercase; }
.jeda-note { font-size: 0.85rem; color: var(--ink-mute); min-height: 2.4em; }
.jeda-timer {
    font-family: monospace;
    font-size: 2.4rem;
    font-weight: 800;
    color: #1f7a34;
    line-height: 1.1;
}
.jeda-btn {
    flex: 1;
    min-height: 64px;
    font-size: 1.15rem;
    font-weight: 800;
    border-radius: 16px;
    border: none;
    background: var(--red);
    color: var(--cream);
}
.jeda-btn:disabled { background: rgba(58,20,20,0.15); color: rgba(58,20,20,0.45); }
.jeda-stop {
    min-height: 56px;
    width: 100%;
    font-weight: 800;
    border-radius: 16px;
    border: 2px solid var(--red);
    background: transparent;
    color: var(--red);
}
</style>
@endsection

@section('content')
<p class="mb-3" style="color:rgba(244,241,221,0.8)">
    Tekan waktu untuk menyalakan meja. Selama itu meja tertutup untuk pelanggan, lalu mati sendiri.
</p>

<div class="jeda-grid">
    @foreach($states as $state)
    @php $table = $state['table']; $pause = $state['pause']; @endphp
    <div class="jeda-card {{ $state['mode'] === 'paused' ? 'is-paused' : '' }} {{ $state['mode'] === 'blocked' ? 'is-blocked' : '' }}">
        <div class="jeda-name">{{ $table->name }}</div>

        @if($state['mode'] === 'paused')
            <div class="jeda-note mb-1">Menyala sampai {{ $pause->ends_at->format('H:i') }}</div>
            <div class="jeda-timer mb-3" data-ends="{{ $pause->ends_at->getTimestamp() }}">--:--</div>
            <form method="POST" action="{{ route('dashboard.jeda.stop', $table) }}">
                @csrf
                <button type="submit" class="jeda-stop">
                    <i class="bi bi-power me-1"></i>Matikan sekarang
                </button>
            </form>
        @elseif($state['mode'] === 'blocked')
            <div class="jeda-note mb-3"><i class="bi bi-slash-circle me-1"></i>{{ $state['note'] }}</div>
            <div class="d-flex gap-2">
                @foreach($minutes as $m)
                <button type="button" class="jeda-btn" disabled>{{ $m }} mnt</button>
                @endforeach
            </div>
        @else
            <div class="jeda-note mb-3">{{ $state['note'] ?? 'Siap dipakai.' }}</div>
            <div class="d-flex gap-2">
                @foreach($minutes as $m)
                <form method="POST" action="{{ route('dashboard.jeda.start', $table) }}" class="d-flex flex-fill">
                    @csrf
                    <input type="hidden" name="minutes" value="{{ $m }}">
                    <button type="submit" class="jeda-btn" {{ ($state['options'][$m] ?? false) ? '' : 'disabled' }}>{{ $m }} mnt</button>
                </form>
                @endforeach
            </div>
        @endif
    </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script>
(function () {
    const timers = Array.from(document.querySelectorAll('.jeda-timer'));
    const serverOffset = {{ now()->getTimestamp() }} * 1000 - Date.now();

    function tick() {
        const now = Date.now() + serverOffset;
        let anyDone = false;

        timers.forEach(el => {
            const left = Math.max(0, Math.round(parseInt(el.dataset.ends, 10) - now / 1000));
            el.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
            if (left <= 0) anyDone = true;
        });

        if (anyDone) {
            setTimeout(() => window.location.reload(), 2500);
            clearInterval(interval);
        }
    }

    const interval = setInterval(tick, 1000);
    tick();

    setTimeout(() => window.location.reload(), 30000);
})();
</script>
@endsection

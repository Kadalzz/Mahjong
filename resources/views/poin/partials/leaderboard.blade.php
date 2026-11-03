@if($podium->isEmpty())
    <div class="pn-page__center">
        <x-poin.state-message icon="inbox" title="Belum ada poin musim ini" desc="Mainkan satu ronde di meja venue untuk masuk ke leaderboard." />
    </div>
    @if($player)
        <x-poin.button :href="route('poin.scan')">Scan Meja</x-poin.button>
    @else
        <x-poin.button :href="route('poin.login')">Masuk untuk ikut bermain</x-poin.button>
    @endif
@else
    <div class="pn-podium-row">
        @foreach([2, 1, 3] as $place)
            @php $step = $podium->firstWhere('rank', $place); @endphp
            @if($step)
                <x-poin.podium-step :rank="$place" :name="$step->nickname" :score="$step->points" class="pn-rise" />
            @else
                <span class="pn-podium pn-podium--empty"></span>
            @endif
        @endforeach
    </div>
    @if($updated)
        <div class="pn-update-row">
            <x-poin.icon name="rotate-cw" size="14" tone="cream" />
            <p class="t-caption-bold">{{ $updated }}</p>
        </div>
    @endif
    <div class="pn-rank-list">
        @foreach($others as $row)
            <x-poin.rank-row :row="$row" class="pn-rise" />
        @endforeach
    </div>
    <span class="pn-board-gap"></span>
    @if($mine)
        <x-poin.player-row size="compact" highlight :rank="$mine->rank" name="Kamu" :meta="$myMeta" :score="$mine->points" />
    @elseif($player)
        <x-poin.button :href="route('poin.scan')">Scan Meja</x-poin.button>
    @else
        <x-poin.button :href="route('poin.login')">Masuk untuk ikut bermain</x-poin.button>
    @endif
@endif

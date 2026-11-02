@props(['rank' => 1, 'name', 'score', 'src' => null])
<div {{ $attributes->class(['pn-podium', "pn-podium--{$rank}"]) }}>
    @if((int) $rank === 1)<p class="t-kicker">JUARA</p>@endif
    <x-poin.avatar :size="(int) $rank === 1 ? 'podium-1' : 'podium'" :src="$src" />
    <p class="t-title-sm">{{ $name }}</p>
    <div class="pn-podium__score">
        <x-poin.icon name="diamond" size="14" />
        <p @class(['pn-podium__score-value', 't-h5' => (int) $rank === 1, 't-label-strong' => (int) $rank !== 1])>{{ $score }}</p>
    </div>
    <div class="pn-pedestal">
        <span class="pn-pedestal__body"></span>
        <p @class(['pn-pedestal__rank', 't-h1' => (int) $rank === 1, 't-h2' => (int) $rank !== 1])>{{ $rank }}</p>
        <img class="pn-pedestal__cap" src="{{ asset("images/poin/parts/cap-{$rank}.svg") }}" alt="">
        <span class="pn-pedestal__rim"></span>
        <img class="pn-pedestal__stars" src="{{ asset('images/poin/parts/stars.svg') }}" alt="">
    </div>
</div>

@props(['size' => 'large', 'highlight' => false, 'rank', 'name', 'score', 'delta' => null, 'meta' => null, 'src' => null])
<div {{ $attributes->class(['pn-player-row', "pn-player-row--{$size}", 'is-highlight' => $highlight]) }}>
    @if($size === 'large')
        <p class="pn-player-row__rank t-h2">{{ $rank }}</p>
        <x-poin.avatar size="52" :src="$src" />
        <p class="pn-player-row__name t-h4">{{ $name }}</p>
        <div class="pn-player-row__score">
            <p class="pn-player-row__points t-h3">{{ $score }}</p>
            @if($delta !== null)
                <p @class(['pn-player-row__delta t-body-bold', 'is-negative' => str_starts_with((string) $delta, '-')])>{{ $delta }}</p>
            @endif
        </div>
    @else
        <p class="pn-player-row__rank t-h4">{{ $rank }}</p>
        <x-poin.avatar size="40" :src="$src" />
        <div class="pn-player-row__name">
            <p class="t-title-sm">{{ $name }}</p>
            @if($meta)<p class="pn-player-row__meta t-body-sm">{{ $meta }}</p>@endif
        </div>
        <p class="pn-player-row__points t-h4">{{ $score }}</p>
    @endif
</div>

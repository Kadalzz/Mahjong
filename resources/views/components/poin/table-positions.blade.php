@props(['you' => 1, 'spin' => true])
<div {{ $attributes->class(['pn-table-map']) }}>
    <span class="pn-table-map__top"></span>
    @if($spin)
        <x-poin.icon name="rotate-cw" size="28" class="pn-table-map__spin" />
        <p class="pn-table-map__caption t-caption">Searah jarum jam</p>
    @endif
    @foreach([3, 2, 1, 4] as $seat)
        @php $wind = \App\Models\GamePlayer::windFor($seat); @endphp
        <span @class(['pn-table-map__seat t-title-sm', 'pn-table-map__seat--p' . $seat, 'is-you' => $seat === (int) $you]) title="{{ $wind }}" aria-label="{{ $wind }}">{{ mb_substr($wind, 0, 1) }}</span>
    @endforeach
</div>

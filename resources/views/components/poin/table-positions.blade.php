@props(['you' => 'P1'])
<div {{ $attributes->class(['pn-table-map']) }}>
    <span class="pn-table-map__top"></span>
    <x-poin.icon name="rotate-cw" size="28" class="pn-table-map__spin" />
    <p class="pn-table-map__caption t-caption">Searah jarum jam</p>
    @foreach(['P3', 'P2', 'P1', 'P4'] as $seat)
        <span @class(['pn-table-map__seat t-title-sm', 'pn-table-map__seat--' . strtolower($seat), 'is-you' => $seat === $you])>{{ $seat }}</span>
    @endforeach
</div>

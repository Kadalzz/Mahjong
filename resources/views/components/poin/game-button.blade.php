@props(['type' => 'available', 'state' => null, 'href' => null])
@if($type === 'available')
    <a href="{{ $href ?? '#' }}" {{ $attributes->class(['pn-game-btn', "is-{$state}" => $state]) }}>
        <span class="pn-shine"></span>
        <span class="t-h3">CHICKEN HAND</span>
        <span class="t-body-sm">Poin sepanjang tahun · 4 pemain</span>
    </a>
@else
    <div aria-disabled="true" {{ $attributes->class(['pn-game-btn pn-game-btn--locked']) }}>
        <span class="pn-shine"></span>
        <x-poin.icon name="lock" size="26" tone="cream" />
        <span class="t-h3">MCR</span>
        <span class="pn-bubble t-pill">COMING SOON</span>
    </div>
@endif

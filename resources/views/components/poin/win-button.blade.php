@props(['state' => null, 'type' => 'button'])
<button type="{{ $type }}" {{ $attributes->class(['pn-win-btn', "is-{$state}" => $state]) }}>
    <span class="pn-shine"></span>
    <x-poin.icon name="trophy" size="30" tone="white" />
    <span class="t-h3">{{ $slot->isEmpty() ? 'SAYA MENANG' : $slot }}</span>
</button>

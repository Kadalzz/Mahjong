@props(['initial' => 'P', 'state' => null, 'href' => null, 'type' => 'button'])
@php
    $classes = ['pn-option-row', "is-{$state}" => $state];
@endphp
<{{ $href ? 'a' : 'button' }} @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif {{ $attributes->class($classes) }}>
    <span class="pn-row-badge t-h4">{{ $initial }}</span>
    <span class="pn-row-label t-title-lg">{{ $slot }}</span>
    <x-poin.icon name="chevron-right" tone="gold" />
</{{ $href ? 'a' : 'button' }}>

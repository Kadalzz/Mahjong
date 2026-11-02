@props(['icon' => 'user', 'href' => null, 'type' => 'button'])
<{{ $href ? 'a' : 'button' }} @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif {{ $attributes->class(['pn-menu-row']) }}>
    <span class="pn-row-badge"><x-poin.icon :name="$icon" /></span>
    <span class="pn-row-label t-title-lg">{{ $slot }}</span>
    <x-poin.icon name="chevron-right" />
</{{ $href ? 'a' : 'button' }}>

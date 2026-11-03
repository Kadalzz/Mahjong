@props(['icon' => 'user', 'href' => null, 'type' => 'button', 'for' => null, 'as' => null])
@php $tag = $as ?? ($href ? 'a' : ($for ? 'label' : 'button')); @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @elseif($for) for="{{ $for }}" @elseif($tag === 'button') type="{{ $type }}" @endif {{ $attributes->class(['pn-menu-row']) }}>
    <span class="pn-row-badge"><x-poin.icon :name="$icon" /></span>
    <span class="pn-row-label t-title-lg">{{ $slot }}</span>
    <x-poin.icon name="chevron-right" class="pn-menu-row__chevron" />
</{{ $tag }}>

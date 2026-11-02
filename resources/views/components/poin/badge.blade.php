@props(['type' => 'ready'])
@php
    $defaults = ['ready' => 'READY', 'auto' => 'AUTO', 'waiting' => 'TUNGGU'];
@endphp
<span {{ $attributes->class(['pn-badge t-pill', "pn-badge--{$type}"]) }}>
    @if(in_array($type, ['ready', 'auto'], true))<span class="pn-badge__dot"></span>@endif
    {{ $slot->isEmpty() ? ($defaults[$type] ?? '') : $slot }}
</span>

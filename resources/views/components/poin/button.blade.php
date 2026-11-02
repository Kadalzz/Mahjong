@props(['kind' => 'primary', 'state' => null, 'href' => null, 'type' => 'button', 'compact' => false, 'disabled' => false])
@php
    $classes = ['pn-btn t-button', "pn-btn--{$kind}", 'pn-btn--compact' => $compact, "is-{$state}" => $state, 'is-disabled' => $disabled];
@endphp
@if($href && !$disabled)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif

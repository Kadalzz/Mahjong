@props(['kind' => 'primary', 'state' => null, 'href' => null, 'type' => 'button', 'compact' => false, 'loading' => false, 'disabled' => false])
@php
    $classes = ['pn-btn t-button', "pn-btn--{$kind}", 'pn-btn--compact' => $compact, "is-{$state}" => $state, 'is-disabled' => $disabled || $loading];
@endphp
@if($href && !$disabled && !$loading)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" @disabled($disabled || $loading) {{ $attributes->class($classes) }}>
        @if($loading)<x-poin.spinner size="button" />@endif
        {{ $slot }}
    </button>
@endif

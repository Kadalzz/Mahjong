@props(['selected' => false, 'href' => null])
@php
    $classes = ['pn-tab t-tab', 'is-selected' => $selected];
@endphp
@if($href)
    <a href="{{ $href }}" @if($selected) aria-current="page" @endif {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" aria-pressed="{{ $selected ? 'true' : 'false' }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif

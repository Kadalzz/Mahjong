@props(['icon' => 'chevron-left', 'label', 'state' => null, 'href' => null, 'type' => 'button'])
@php
    $classes = ['pn-icon-btn', "is-{$state}" => $state];
@endphp
@if($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" {{ $attributes->class($classes) }}><x-poin.icon :name="$icon" /></a>
@else
    <button type="{{ $type }}" aria-label="{{ $label }}" {{ $attributes->class($classes) }}><x-poin.icon :name="$icon" /></button>
@endif

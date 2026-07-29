@props(['size' => 32, 'variant' => 'light'])

@php
    $src = $variant === 'dark'
        ? asset('images/logo-mark-dark.png')
        : asset('images/logo-mark-light.png');
    $width = round($size * 538 / 404);
@endphp

<img {{ $attributes->merge(['class' => 'brand-logo-mark']) }} src="{{ $src }}" alt="Hóng Zhōng Mahjong" width="{{ $width }}" height="{{ $size }}">

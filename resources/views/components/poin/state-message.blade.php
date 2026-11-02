@props(['icon' => 'wifi-off', 'title', 'desc'])
<div {{ $attributes->class(['pn-state']) }}>
    <span class="pn-state__icon"><x-poin.icon :name="$icon" size="40" /></span>
    <p class="pn-state__title t-h3">{{ $title }}</p>
    <p class="pn-state__desc t-body">{{ $desc }}</p>
    {{ $slot }}
</div>

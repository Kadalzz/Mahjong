@props(['status' => 'ready', 'you' => false, 'name' => null, 'src' => null])
<div {{ $attributes->class(['pn-seat', 'pn-seat--you' => $you]) }}>
    <x-poin.avatar size="48" :src="$src" />
    <p class="t-title-sm">{{ $you ? 'Kamu' : $name }}</p>
    <x-poin.badge :type="$status === 'ready' ? 'ready' : 'waiting'" />
</div>

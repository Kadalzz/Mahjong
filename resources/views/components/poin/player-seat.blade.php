@props(['status' => 'ready', 'you' => false, 'name' => null, 'src' => null, 'wind' => null, 'badge' => null])
<div {{ $attributes->class(['pn-seat', 'pn-seat--you' => $you]) }}>
    @if($wind)<p class="pn-seat__wind t-caption">{{ $wind }}</p>@endif
    <x-poin.avatar size="48" :src="$src" />
    <p class="pn-seat__name t-title-sm">{{ $you ? 'Kamu' : $name }}</p>
    <x-poin.badge :type="$status === 'ready' ? 'ready' : 'waiting'">{{ $badge }}</x-poin.badge>
</div>

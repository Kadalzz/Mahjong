@props(['type' => 'available'])
@php
    $available = $type === 'available';
@endphp
<div {{ $attributes->class(['pn-info-card', $available ? 'pn-info-card--available' : 'pn-info-card--coming']) }}>
    <x-poin.mahjong-tile :tone="$available ? 'light' : 'dim'" />
    <div class="pn-info-card__text">
        <div class="pn-info-card__head">
            <p class="pn-info-card__title t-h4">{{ $available ? 'Chicken Hand' : 'MCR' }}</p>
            @if($available)
                <x-poin.badge type="auto">TERSEDIA</x-poin.badge>
            @else
                <x-poin.badge type="outline">COMING SOON</x-poin.badge>
            @endif
        </div>
        <p class="pn-info-card__desc t-body">{{ $available ? 'Permainan 4 pemain di meja venue. Poin tiap game tercatat di leaderboard sepanjang tahun.' : 'Permainan MCR sedang disiapkan. Nantikan segera.' }}</p>
    </div>
</div>

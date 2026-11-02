@props(['type' => 'available', 'href' => null])
@if($type === 'available')
    <div {{ $attributes->class(['pn-game-card pn-game-card--available']) }}>
        <div class="pn-game-card__top">
            <x-poin.mahjong-tile />
            <p class="pn-game-card__title t-h2">Chicken Hand</p>
            <p class="pn-game-card__desc t-body">Hitung point &amp; Win untuk 4 pemain.</p>
        </div>
        <x-poin.button kind="secondary" :href="$href">Main</x-poin.button>
        <span class="pn-game-card__frame"></span>
    </div>
@else
    <div {{ $attributes->class(['pn-game-card pn-game-card--coming']) }}>
        <div class="pn-game-card__top">
            <x-poin.mahjong-tile tone="dim" />
            <p class="pn-game-card__coming-title t-h1">MCR</p>
        </div>
        <div class="pn-game-card__bottom">
            <x-poin.icon name="arrow-down" />
            <x-poin.badge type="outline">COMING SOON</x-poin.badge>
        </div>
    </div>
@endif

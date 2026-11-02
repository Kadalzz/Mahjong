@props(['title' => 'Aturan main'])
<div {{ $attributes->class(['pn-notice']) }}>
    <x-poin.icon name="info" />
    <div class="pn-notice__text">
        <p class="pn-notice__title t-body-bold">{{ $title }}</p>
        <p class="t-body">{{ $slot->isEmpty() ? 'Giliran berjalan searah jarum jam: P1, P2, P3, lalu P4.' : $slot }}</p>
    </div>
</div>

@extends('layouts.poin')

@section('title', 'MCR')

@section('content')
<main class="pn-page pn-page--gap-4">
    <x-poin.app-bar title="MCR" :back="route('poin.games')" />
    <x-poin.ornament />
    <span class="pn-page__spacer"></span>
    <div class="pn-coming">
        <x-poin.mahjong-tile tone="dim-large" class="pn-tile--large" />
        <x-poin.badge type="outline">COMING SOON</x-poin.badge>
        <p class="pn-coming__title t-display-hero">MCR</p>
        <p class="pn-muted t-body-lg text-center">Permainan MCR sedang disiapkan. Nantikan segera di Hóng Zhōng Mahjong.</p>
    </div>
    <span class="pn-page__spacer"></span>
    <x-poin.button :href="route('poin.games')">Kembali ke Pilih Game</x-poin.button>
</main>
@endsection

@extends('layouts.poin')

@section('title', 'Scan Meja')

@section('content')
<main class="pn-page pn-page--gap-4">
    <x-poin.app-bar title="Scan Meja" :back="route('poin.scan')" profile />
    <x-poin.loader :url="route('poin.table.content', $code)" class="pn-page__body pn-page--gap-4" :not-found="route('poin.scan', ['status' => 'unknown'])"
        offline-desc="Meja belum bisa diperiksa. Sambungkan internet lalu coba lagi." :secondary-href="route('poin.scan')" secondary-label="Scan Ulang">
        <x-slot:loading>
            <div class="pn-skeleton-card pn-skeleton-card--tall">
                <x-poin.skeleton class="pn-skeleton--avatar" />
                <div class="pn-skeleton-card__text">
                    <x-poin.skeleton class="pn-skeleton--title" />
                    <x-poin.skeleton class="pn-skeleton--line" />
                </div>
            </div>
            <div class="pn-skeleton-card">
                <x-poin.skeleton class="pn-skeleton--avatar" />
                <div class="pn-skeleton-card__text">
                    <x-poin.skeleton class="pn-skeleton--title" />
                    <x-poin.skeleton class="pn-skeleton--line" />
                </div>
            </div>
            <span class="pn-page__spacer"></span>
            <x-poin.button disabled>Memeriksa meja…</x-poin.button>
        </x-slot:loading>
    </x-poin.loader>
</main>
@endsection

@extends('layouts.poin')

@section('title', 'Pilih Permainan')

@section('content')
<main class="pn-page pn-page--gap-3-5">
    <img class="pn-watermark pn-watermark--games" src="{{ asset('images/poin/illustrations/watermark.svg') }}" alt="">
    <x-poin.brand-lockup class="w-full" />
    <x-poin.profile-link class="pn-games__profile" />
    <x-poin.ornament />
    <h1 class="pn-page__title t-h1 text-center">Pilih Permainan</h1>
    <p class="pn-subtle t-body-lg text-center">Satu meja, empat pemain, satu pemenang.</p>
    <x-poin.loader :url="route('poin.games.content')" class="pn-page__body" error-title="Gagal memuat permainan">
        <x-slot:loading>
            <div class="pn-stack">
                @foreach([1, 2] as $card)
                    <div class="pn-skeleton-card">
                        <x-poin.skeleton class="pn-skeleton--avatar" />
                        <div class="pn-skeleton-card__text">
                            <x-poin.skeleton class="pn-skeleton--title" />
                            <x-poin.skeleton class="pn-skeleton--line" />
                        </div>
                    </div>
                @endforeach
            </div>
        </x-slot:loading>
    </x-poin.loader>
</main>
@endsection

@extends('layouts.poin')

@section('title', 'Hóng Zhōng Mahjong')

@section('content')
<main class="pn-page pn-page--gap-4">
    <x-poin.brand-lockup class="w-full" />
    <x-poin.loader :url="route('poin.home.content')" class="pn-page__body pn-page--gap-4">
        <x-slot:loading>
            <div class="pn-loader__center pn-loader__center--spinner">
                <x-poin.spinner />
                <p class="pn-muted t-body">Memuat…</p>
            </div>
        </x-slot:loading>
    </x-poin.loader>
</main>
@endsection

@extends('layouts.poin')

@section('title', 'Lobby')

@section('content')
<main class="pn-page pn-page--gap-4" data-poin-poll data-url="{{ route('poin.lobby.status', $game) }}">
    <header class="pn-app-bar">
        <form method="POST" action="{{ route('poin.lobby.leave', $game) }}">
            @csrf
            <x-poin.icon-button type="submit" label="Kembali" />
        </form>
        <h1 class="t-title-lg">Lobby</h1>
        <a href="#" aria-label="Profil" class="pn-icon-link"><x-poin.avatar size="40" /></a>
    </header>
    <x-poin.status-banner tone="warning" data-poin-poll-banner hidden>Koneksi terputus. Menyambungkan kembali…</x-poin.status-banner>
    <div class="pn-poll-loading" data-poin-poll-loading>
        <x-poin.skeleton class="pn-skeleton--notice" />
        <x-poin.skeleton class="pn-skeleton--lobby" />
        <span class="pn-page__spacer"></span>
        <x-poin.button disabled>Menyambungkan ke meja…</x-poin.button>
    </div>
    <div class="pn-poll-content" data-poin-poll-content hidden></div>
</main>
@endsection

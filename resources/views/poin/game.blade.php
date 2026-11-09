@extends('layouts.poin')

@section('title', $game->table->displayName())

@section('content')
<main class="pn-page pn-page--gap-3-5" data-poin-poll data-url="{{ route('poin.game.status', $game) }}">
    <x-poin.app-bar :title="$game->table->displayName()" :back="route('poin.games')" profile />
    @if($notice)
        <x-poin.status-banner tone="warning">{{ $notice }}</x-poin.status-banner>
    @endif
    <x-poin.status-banner tone="warning" data-poin-poll-banner hidden>Koneksi terputus. Menyambungkan kembali…</x-poin.status-banner>
    <div class="pn-poll-loading" data-poin-poll-loading>
        <x-poin.skeleton class="pn-skeleton--lobby" />
    </div>
    <div class="pn-poll-content" data-poin-poll-content hidden></div>
</main>
@endsection

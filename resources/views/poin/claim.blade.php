@extends('layouts.poin')

@section('title', 'Konfirmasi Kemenangan')

@section('content')
<main class="pn-page pn-page--gap-3-5" data-poin-poll data-poin-interval="1000" data-url="{{ route('poin.round.claim.status', ['game' => $game, 'klaim' => $claim->id]) }}">
    <x-poin.app-bar :title="$game->table->displayName()" profile />
    <x-poin.status-banner tone="warning" data-poin-poll-banner hidden>Koneksi terputus. Menyambungkan kembali…</x-poin.status-banner>
    <div class="pn-poll-loading" data-poin-poll-loading>
        <x-poin.skeleton class="pn-skeleton--lobby" />
    </div>
    <div class="pn-poll-content" data-poin-poll-content hidden></div>
</main>
@endsection

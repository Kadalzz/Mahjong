@php
    $readyCount = $game->seats->where('is_ready', true)->count();
    $ready = $game->isReadyToStart();
@endphp
<x-poin.notice class="pn-online-only" />
<x-poin.lobby-table :game="$game" :player="$player" />
<span class="pn-page__spacer"></span>
<p class="pn-online-only {{ $ready ? 'pn-lobby-hint--ready' : 'pn-subtle' }} t-body text-center">
    {{ $ready ? 'Semua pemain siap (4/4)' : "Menunggu semua pemain Ready ({$readyCount}/4)" }}
</p>
<p class="pn-offline-only pn-subtle t-body text-center">Menunggu koneksi tersambung…</p>
<form method="POST" action="{{ route('poin.lobby.start', $game) }}">
    @csrf
    <x-poin.button type="submit" :disabled="!$ready" data-poin-needs-connection>Start Game</x-poin.button>
</form>

@php
    $readyCount = $game->seats->where('is_ready', true)->count();
    $ready = $game->isReadyToStart();
    $mine = $game->seats->firstWhere('player_id', $player->id);
@endphp
<x-poin.notice class="pn-online-only" />
<x-poin.lobby-table :game="$game" :player="$player" />
<p class="pn-online-only pn-subtle t-body-sm text-center">Ketuk kursi kosong untuk pindah, atau kursi pemain lain untuk bertukar tempat.</p>
<span class="pn-page__spacer"></span>
<p class="pn-online-only {{ $ready ? 'pn-lobby-hint--ready' : 'pn-subtle' }} t-body text-center">
    {{ $ready ? 'Semua pemain siap (4/4)' : "Menunggu semua pemain Ready ({$readyCount}/4)" }}
</p>
<p class="pn-offline-only pn-subtle t-body text-center">Menunggu koneksi tersambung…</p>
<form method="POST" action="{{ route('poin.lobby.ready', $game) }}">
    @csrf
    <input type="hidden" name="ready" value="{{ $mine?->is_ready ? 0 : 1 }}">
    <x-poin.button type="submit" :kind="$mine?->is_ready ? 'ghost' : 'secondary'" data-poin-needs-connection>{{ $mine?->is_ready ? 'Batal Ready' : 'Ready' }}</x-poin.button>
</form>
<form method="POST" action="{{ route('poin.lobby.start', $game) }}">
    @csrf
    <x-poin.button type="submit" :disabled="!$ready" data-poin-needs-connection>Start Game</x-poin.button>
</form>

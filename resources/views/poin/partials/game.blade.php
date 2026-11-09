<x-poin.ornament />
<div class="flex justify-center"><x-poin.badge type="outline">RONDE {{ $game->current_round }}</x-poin.badge></div>
<div class="pn-positions">
    <p class="t-kicker">POSISI PEMAIN</p>
    <x-poin.table-positions :you="$seat->seat" class="pn-table-map--game" />
</div>
<span class="pn-page__spacer"></span>
<div class="pn-player-card">
    <x-poin.avatar size="96" :src="$seat->player->avatarUrl()" />
    <div class="pn-player-card__info">
        <p class="pn-truncate t-h4">Kamu · {{ $seat->player->nickname }}</p>
        <p class="pn-player-card__points t-body-bold">{{ $points }} poin</p>
    </div>
</div>
<div class="pn-win-wrap">
    <span class="pn-win-ring pn-online-only"></span>
    <form method="GET" action="{{ route('poin.round.win', $game) }}">
        <x-poin.win-button type="submit" data-poin-needs-connection />
    </form>
</div>
<span class="pn-page__spacer"></span>
<p class="pn-online-only pn-muted t-body text-center">Tekan tombol saat kamu menang</p>
<p class="pn-offline-only pn-muted t-body text-center">Tombol aktif lagi setelah tersambung.</p>

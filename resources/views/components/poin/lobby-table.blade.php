@props(['game', 'player'])
@php
    $seats = $game->seats->keyBy('seat');
@endphp
<div {{ $attributes->class(['pn-lobby-table']) }}>
    <span class="pn-lobby-table__edge"></span>
    <span class="pn-lobby-table__felt"></span>
    <div class="pn-lobby-table__center">
        <p class="t-kicker">MEJA</p>
        <p class="pn-lobby-table__number t-display-hero">{{ $game->table->number() }}</p>
        <x-poin.badge type="auto" />
    </div>
    @foreach([3, 4, 2, 1] as $number)
        @php
            $seat = $seats->get($number);
            $wind = \App\Models\GamePlayer::windFor($number);
        @endphp
        @if($seat)
            <x-poin.player-seat :wind="$wind" :name="$seat->player->nickname" :you="$seat->player_id === $player->id" :src="$seat->player->avatarUrl()" :status="$seat->is_ready ? 'ready' : 'waiting'" class="pn-lobby-table__seat pn-lobby-table__seat--p{{ $number }}" />
        @else
            <form method="POST" action="{{ route('poin.lobby.seat', $game) }}" class="pn-lobby-table__seat pn-lobby-table__seat--p{{ $number }}">
                @csrf
                <input type="hidden" name="kursi" value="{{ $number }}">
                <button type="submit" class="pn-seat-pick" aria-label="Pindah ke kursi {{ $wind }}" data-poin-needs-connection>
                    <x-poin.player-seat :wind="$wind" name="Kosong" status="waiting" badge="PILIH" />
                </button>
            </form>
        @endif
    @endforeach
</div>

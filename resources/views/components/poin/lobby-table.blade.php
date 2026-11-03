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
            $you = $seat?->player_id === $player->id;
        @endphp
        <x-poin.player-seat :name="$seat?->player->nickname ?? 'P' . $number" :you="$you" :src="$seat?->player->avatarUrl()" :status="$seat?->is_ready ? 'ready' : 'waiting'" class="pn-lobby-table__seat pn-lobby-table__seat--p{{ $number }}" />
    @endforeach
</div>

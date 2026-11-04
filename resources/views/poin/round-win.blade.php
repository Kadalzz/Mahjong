@extends('layouts.poin')

@section('title', 'Poin Menang')

@section('content')
<main class="pn-page pn-flow" data-poin-flow data-poin-watch data-url="{{ route('poin.round.result.status', ['game' => $game, 'ronde' => $round->number]) }}">
    <section class="pn-flow__step pn-page--gap-3-5" data-poin-step="main">
        <header class="pn-app-bar">
            @if($canUndo)
                <form method="POST" action="{{ route('poin.round.undo', $game) }}">
                    @csrf
                    <input type="hidden" name="ronde" value="{{ $round->number }}">
                    <x-poin.icon-button type="submit" label="Batalkan hasil ronde ini" />
                </form>
            @else
                <span class="pn-app-bar__spacer"></span>
            @endif
            <h1 class="t-title-lg">Poin Menang</h1>
            <x-poin.profile-link />
        </header>
        <x-poin.ornament />
        <div class="flex justify-center"><x-poin.badge type="outline">RONDE {{ $round->number }}</x-poin.badge></div>
        <p class="t-kicker text-center">KAMU MENANG · DARI {{ $discarder ? Str::upper($discarder->nickname) : 'DECK' }}</p>
        <div class="pn-scoreboard">
            @foreach($standings as $row)
                @php $you = $row->player_id === $seat->player_id; @endphp
                <x-poin.player-row
                    :highlight="$you"
                    :rank="$row->rank"
                    :name="$you ? 'Kamu' : $row->nickname"
                    :score="$row->points"
                    :src="$row->avatar"
                    :delta="$row->delta > 0 ? '+' . $row->delta : (string) $row->delta"
                    class="pn-rise"
                />
            @endforeach
        </div>
        <span class="pn-page__spacer"></span>
        @include('poin.partials.round-actions')
    </section>
    @include('poin.partials.round-failed')
</main>
@endsection

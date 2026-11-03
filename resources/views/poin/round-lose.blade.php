@extends('layouts.poin')

@section('title', 'Yah, Kamu Kalah')

@section('content')
@php
    $source = $discarder ? ($discarder->player_id === $seat->player_id ? 'kamu' : $discarder->nickname) : 'Deck';
    $loss = str_replace('-', '−', (string) $mine->delta);
@endphp
<main class="pn-page pn-flow" data-poin-flow data-poin-watch data-url="{{ route('poin.round.result.status', ['game' => $game, 'ronde' => $round->number]) }}">
    <section class="pn-flow__step pn-flow__step--center pn-page--gap-4" data-poin-step="main">
        <span class="pn-page__spacer"></span>
        <div class="pn-result-hero">
            <x-poin.illustration name="lose-badge" />
            <p class="t-h1 text-center">Yah, Kamu Kalah</p>
            <p class="pn-muted t-body-lg text-center">{{ $winner->nickname }} menang dari {{ $source }}. Poinmu berkurang {{ $loss }}.</p>
            <div class="pn-chip-row">
                @foreach($standings->sortBy('seat') as $row)
                    <x-poin.score-chip
                        :tone="$row->delta > 0 ? 'win' : 'lose'"
                        :label="$row->player_id === $seat->player_id ? $row->nickname . ' · Kamu' : $row->nickname"
                        :value="$row->delta > 0 ? '+' . $row->delta : (string) $row->delta"
                    />
                @endforeach
            </div>
        </div>
        <span class="pn-page__spacer"></span>
        @include('poin.partials.round-actions')
    </section>
    @include('poin.partials.round-failed', ['bare' => true])
</main>
@endsection

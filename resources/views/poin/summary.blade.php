@extends('layouts.poin')

@section('title', 'Sesi Selesai')

@section('content')
<main class="pn-page pn-page--gap-4">
    <x-poin.ornament />
    <span class="pn-page__spacer"></span>
    <div class="pn-summary">
        <p class="t-kicker">SESI SELESAI</p>
        <p class="t-h2 text-center">Terima kasih sudah bermain</p>
        <p class="pn-muted t-body-lg text-center">Poin kamu sudah tercatat di leaderboard Chicken Hand.</p>
    </div>
    <div class="pn-stat-row">
        <div class="pn-stat">
            <p class="pn-stat__label t-caption">POIN SESI</p>
            <p @class(['pn-stat__value t-h3', 'is-positive' => $mine->points > 0])>{{ $mine->points > 0 ? '+' . $mine->points : $mine->points }}</p>
        </div>
        <div class="pn-stat">
            <p class="pn-stat__label t-caption">MENANG</p>
            <p class="pn-stat__value t-h3">{{ $mine->wins }}x</p>
        </div>
        <div class="pn-stat">
            <p class="pn-stat__label t-caption">POSISI</p>
            <p class="pn-stat__value t-h3">{{ $row?->rank ?? '–' }}</p>
        </div>
    </div>
    @if($row)
        <x-poin.player-row size="compact" highlight :rank="$row->rank" name="Kamu" :meta="$meta" :score="$row->points" :src="auth('player')->user()->avatarUrl()" />
    @endif
    <span class="pn-page__spacer"></span>
    <x-poin.button :href="route('poin.leaderboard')">Lihat Leaderboard</x-poin.button>
    <x-poin.button kind="ghost" :href="route('poin.home')">Ke Beranda</x-poin.button>
</main>
@endsection

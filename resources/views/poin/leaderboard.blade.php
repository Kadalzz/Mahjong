@extends('layouts.poin')

@section('title', 'Leaderboard')

@section('content')
<main class="pn-page pn-page--gap-3 pn-page--board">
    <img class="pn-rays" src="{{ asset('images/poin/illustrations/rays.svg') }}" alt="">
    <x-poin.app-bar title="Leaderboard" :back="$player ? route('poin.games') : route('poin.home')" :profile="(bool) $player" class="pn-layer" />
    <p class="pn-layer t-kicker text-center">{{ $kicker }} · URUT POIN &amp; JUMLAH MENANG</p>
    <x-poin.segmented class="pn-layer">
        <x-poin.tab :selected="$period === 'tahun'" :href="route('poin.leaderboard', ['periode' => 'tahun'])">Tahun Ini</x-poin.tab>
        <x-poin.tab :selected="$period === 'bulan'" :href="route('poin.leaderboard', ['periode' => 'bulan'])">Bulan Ini</x-poin.tab>
        <x-poin.tab :selected="$period === 'minggu'" :href="route('poin.leaderboard', ['periode' => 'minggu'])">Minggu Ini</x-poin.tab>
    </x-poin.segmented>
    <x-poin.loader class="pn-layer pn-board-loader" :url="route('poin.leaderboard.content', ['periode' => $period])" error-title="Leaderboard gagal dimuat" error-desc="Server tidak merespons. Coba lagi beberapa saat.">
        <x-slot:loading>
            <div class="pn-podium-row">
                @foreach([2, 1, 3] as $place)
                    <div class="pn-podium-skeleton pn-podium-skeleton--{{ $place }}">
                        <x-poin.skeleton class="pn-podium-skeleton__avatar" />
                        <x-poin.skeleton class="pn-podium-skeleton__name" />
                        <x-poin.skeleton class="pn-podium-skeleton__score" />
                        <span class="pn-podium-skeleton__gap"></span>
                        <x-poin.skeleton class="pn-podium-skeleton__pedestal" />
                    </div>
                @endforeach
            </div>
            @foreach(range(1, 3) as $row)
                <div class="pn-skeleton-card pn-skeleton-card--row">
                    <x-poin.skeleton class="pn-skeleton--avatar pn-skeleton--avatar-sm" />
                    <div class="pn-skeleton-card__text">
                        <x-poin.skeleton class="pn-skeleton--title" />
                        <x-poin.skeleton class="pn-skeleton--line" />
                    </div>
                </div>
            @endforeach
            <span class="pn-page__spacer"></span>
            <x-poin.skeleton class="pn-skeleton--footer" />
        </x-slot:loading>
    </x-poin.loader>
</main>
@endsection

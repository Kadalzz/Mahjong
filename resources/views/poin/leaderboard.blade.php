@extends('layouts.poin')

@section('title', 'Leaderboard')

@section('content')
<main class="pn-page pn-page--gap-3 pn-page--board">
    <img class="pn-rays" src="{{ asset('images/poin/illustrations/rays.svg') }}" alt="">
    <x-poin.app-bar title="Leaderboard" :back="route('poin.home')" class="pn-layer" />
    <p class="pn-layer t-kicker text-center">{{ $kicker }} · URUT POIN &amp; JUMLAH MENANG</p>
    <x-poin.segmented class="pn-layer">
        <x-poin.tab :selected="$period === 'tahun'" :href="route('poin.leaderboard', ['periode' => 'tahun'])">Tahun Ini</x-poin.tab>
        <x-poin.tab :selected="$period === 'bulan'" :href="route('poin.leaderboard', ['periode' => 'bulan'])">Bulan Ini</x-poin.tab>
        <x-poin.tab :selected="$period === 'minggu'" :href="route('poin.leaderboard', ['periode' => 'minggu'])">Minggu Ini</x-poin.tab>
    </x-poin.segmented>

    @if($podium->isEmpty())
        <div class="pn-layer pn-page__center">
            <x-poin.state-message icon="inbox" title="Belum ada poin musim ini" desc="Mainkan satu ronde di meja venue." />
        </div>
    @else
        <div class="pn-layer pn-podium-row">
            @foreach([2, 1, 3] as $place)
                @php $step = $podium->firstWhere('rank', $place); @endphp
                @if($step)
                    <x-poin.podium-step :rank="$place" :name="$step->nickname" :score="$step->points" class="pn-rise" />
                @else
                    <span class="pn-podium pn-podium--empty"></span>
                @endif
            @endforeach
        </div>
        @if($updated)
            <div class="pn-layer pn-update-row">
                <x-poin.icon name="rotate-cw" size="14" tone="cream" />
                <p class="t-caption-bold">{{ $updated }}</p>
            </div>
        @endif
        <div class="pn-layer pn-rank-list">
            @foreach($others as $row)
                <x-poin.rank-row :row="$row" class="pn-rise" />
            @endforeach
        </div>
    @endif

    <span class="pn-board-gap"></span>
    <x-poin.button :href="route('poin.login')" class="pn-layer">Masuk untuk ikut bermain</x-poin.button>
</main>
@endsection

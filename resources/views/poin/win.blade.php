@extends('layouts.poin')

@section('title', 'Kamu Menang!')

@section('content')
<main class="pn-page pn-flow" data-poin-flow data-poin-watch data-url="{{ route('poin.round.win.status', ['game' => $game, 'ronde' => $game->current_round]) }}">
    <section class="pn-flow__step pn-page--gap-4" data-poin-step="main">
        <x-poin.confetti />
        <span class="pn-page__spacer"></span>
        <div class="pn-result-hero pn-layer">
            <x-poin.illustration name="win-badge" class="pn-medal--pop" />
            <p class="t-h1 text-center">Kamu Menang!</p>
            <p class="pn-muted t-body-lg text-center">Pilih dari mana kamu menang: dari pemain lain atau dari deck.</p>
        </div>
        <span class="pn-page__spacer"></span>
        <x-poin.button class="pn-layer" data-poin-sheet-open>Pilih Sumber Kemenangan</x-poin.button>
    </section>

    <button type="button" class="pn-sheet-dim" data-poin-sheet-close aria-label="Tutup"></button>
    <div class="pn-sheet" data-poin-sheet role="dialog" aria-modal="true" aria-labelledby="pn-win-from">
        <div class="pn-sheet__handle-row"><span class="pn-sheet__handle"></span></div>
        <p class="pn-sheet__kicker t-kicker">{{ Str::upper($seat->player->nickname) }} MENANG DARI</p>
        <h2 id="pn-win-from" class="pn-sheet__title t-h2">Win From</h2>
        <div class="pn-sheet__options">
            @foreach(array_merge([['value' => 'deck', 'initial' => 'D', 'label' => 'Deck']], $options->map(fn ($other) => ['value' => $other->seat, 'initial' => Str::upper(mb_substr($other->player->nickname, 0, 1)), 'label' => $other->player->nickname])->all()) as $option)
                <form method="POST" action="{{ route('poin.round.store', $game) }}" data-poin-action>
                    @csrf
                    <input type="hidden" name="ronde" value="{{ $game->current_round }}">
                    <input type="hidden" name="dari" value="{{ $option['value'] }}">
                    <x-poin.option-row type="submit" :initial="$option['initial']"><span class="pn-truncate">{{ $option['label'] }}</span></x-poin.option-row>
                </form>
            @endforeach
        </div>
    </div>

    <section class="pn-flow__step pn-page--gap-3-5" data-poin-step="saving" hidden>
        <header class="pn-app-bar">
            <x-poin.icon-button label="Kembali" disabled />
            <h1 class="t-title-lg">Poin Menang</h1>
            <x-poin.profile-link />
        </header>
        <x-poin.ornament />
        <div class="flex justify-center"><x-poin.badge type="outline">RONDE {{ $game->current_round }}</x-poin.badge></div>
        <p class="t-kicker">MENYIMPAN POIN…</p>
        @foreach(range(1, 4) as $row)
            <div class="pn-skeleton-card pn-skeleton-card--score">
                <x-poin.skeleton class="pn-skeleton--avatar pn-skeleton--avatar-sm" />
                <div class="pn-skeleton-card__text">
                    <x-poin.skeleton class="pn-skeleton--title" />
                    <x-poin.skeleton class="pn-skeleton--line" />
                </div>
            </div>
        @endforeach
        <span class="pn-page__spacer"></span>
        <x-poin.button disabled>Menyimpan…</x-poin.button>
    </section>

    <section class="pn-flow__step pn-page--gap-3-5" data-poin-step="failed" hidden>
        <header class="pn-app-bar">
            <x-poin.icon-button label="Kembali" data-poin-step-back />
            <h1 class="t-title-lg">Poin Menang</h1>
            <x-poin.profile-link />
        </header>
        <x-poin.ornament />
        <div class="flex justify-center"><x-poin.badge type="outline">RONDE {{ $game->current_round }}</x-poin.badge></div>
        <div class="pn-loader__center">
            <x-poin.state-message icon="alert" title="Poin belum tersimpan" desc="Koneksi terputus saat menyimpan. Coba simpan lagi." />
        </div>
        <x-poin.button data-poin-action-retry>Coba Simpan Lagi</x-poin.button>
        @include('poin.partials.stop-session')
    </section>
</main>
@endsection

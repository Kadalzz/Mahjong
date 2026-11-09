@php
    $fmt = fn (int $value) => $value > 0 ? '+' . $value : str_replace('-', '−', (string) $value);
@endphp
<x-poin.ornament />
<div class="flex justify-center"><x-poin.badge type="outline">RONDE {{ $claim->number }}</x-poin.badge></div>
@if($isWinner)
    <p class="t-kicker text-center">MENUNGGU KONFIRMASI · {{ $agreed }}/{{ $others->count() }}</p>
    <div class="pn-result-hero">
        <p class="t-h2 text-center">Kamu menang dari {{ $source }}</p>
        <p class="pn-muted t-body text-center">Poin tercatat setelah pemain lain setuju. Pemain yang tidak menjawab dianggap setuju.</p>
    </div>
    <div class="pn-info-rows t-body">
        @foreach($others as $other)
            <div class="pn-info-rows__row">
                <span class="pn-truncate">{{ $other->nickname }}</span>
                <span class="pn-info-rows__value t-body-bold">{{ $other->agreed ? 'Setuju' : ($other->seen ? "Menunggu · {$other->seconds} dtk" : 'Belum membuka') }}</span>
            </div>
        @endforeach
    </div>
    <span class="pn-page__spacer"></span>
    <form method="POST" action="{{ route('poin.round.claim.withdraw', $game) }}">
        @csrf
        <input type="hidden" name="klaim" value="{{ $claim->id }}">
        <x-poin.button type="submit" kind="ghost" data-poin-needs-connection>Batalkan Klaim</x-poin.button>
    </form>
@else
    <p class="t-kicker text-center">KONFIRMASI KEMENANGAN</p>
    <div class="pn-result-hero">
        <p class="t-h2 text-center">{{ $claim->winner->nickname }} menang dari {{ $source }}</p>
        <p class="pn-muted t-body text-center">Poinmu akan berkurang {{ $fmt($delta) }}.</p>
        <div class="pn-chip-row">
            @foreach($chips as $chip)
                <x-poin.score-chip :tone="$chip->delta > 0 ? 'win' : 'lose'" :label="$chip->label" :value="$fmt($chip->delta)" />
            @endforeach
        </div>
    </div>
    <span class="pn-page__spacer"></span>
    @if($mine?->agreed_at)
        <p class="pn-muted t-body text-center">Kamu sudah setuju. Menunggu pemain lain ({{ $agreed }}/{{ $others->count() }}).</p>
    @else
        <p class="t-body-bold text-center">Otomatis setuju dalam {{ $seconds }} detik</p>
        <form method="POST" action="{{ route('poin.round.claim.agree', $game) }}">
            @csrf
            <input type="hidden" name="klaim" value="{{ $claim->id }}">
            <x-poin.button type="submit" data-poin-needs-connection>Setuju</x-poin.button>
        </form>
        <form method="POST" action="{{ route('poin.round.claim.reject', $game) }}">
            @csrf
            <input type="hidden" name="klaim" value="{{ $claim->id }}">
            <x-poin.button type="submit" kind="ghost" data-poin-needs-connection>Tolak</x-poin.button>
        </form>
    @endif
@endif

@extends('layouts.poin')

@section('title', 'Bantuan')

@use('App\Services\Poin\PointCalculator', 'Points')
@php
    $signed = fn (int $value) => ($value > 0 ? '+' : '−') . abs($value);
    $faqs = [
        'Main di venue'  => 'Poin hanya tercatat saat bermain di meja venue. Scan QR di meja, tunggu empat pemain siap, lalu tekan Start Game.',
        'Urutan giliran' => 'Giliran berjalan searah jarum jam mengikuti posisi kursi di meja. Posisimu terlihat di layar permainan.',
        'Poinku salah?'  => 'Pemain yang mencatat kemenangan bisa membatalkan hasil ronde terakhir lewat tombol kembali di layar Poin Menang. Untuk ronde sebelumnya, hubungi staf venue.',
        'Lupa sandi?'    => 'Temui staf atau admin venue untuk mengatur ulang sandimu. Setelah masuk dengan sandi sementara, ganti sandi lewat Profil.',
    ];
@endphp

@section('content')
<main class="pn-page pn-page--gap-2-5">
    <x-poin.app-bar title="Bantuan" :back="$back" />
    <p class="pn-help__kicker t-kicker">CARA HITUNG POIN</p>
    <div class="pn-rules">
        <div class="pn-rules__item">
            <p class="t-label-strong">Menang dari tile pemain lain</p>
            <p class="pn-rules__desc t-body-sm">Pemenang {{ $signed(Points::WIN_FROM_PLAYER) }} · yang membuang tile {{ $signed(Points::DISCARDER) }} · dua pemain lain masing-masing {{ $signed(Points::OTHER_FROM_PLAYER) }}</p>
        </div>
        <span class="pn-rules__divider"></span>
        <div class="pn-rules__item">
            <p class="t-label-strong">Menang dari deck</p>
            <p class="pn-rules__desc t-body-sm">Pemenang {{ $signed(Points::WIN_FROM_DECK) }} · tiga pemain lain masing-masing {{ $signed(Points::OTHER_FROM_DECK) }}</p>
        </div>
    </div>
    <p class="pn-help__kicker t-kicker">PERTANYAAN UMUM</p>
    @foreach($faqs as $question => $answer)
        <details class="pn-faq">
            <x-poin.menu-row as="summary" icon="info">{{ $question }}</x-poin.menu-row>
            <p class="pn-faq__answer t-body">{{ $answer }}</p>
        </details>
    @endforeach
    <span class="pn-page__spacer"></span>
    @if($staffUrl)
        <x-poin.button kind="ghost" :href="$staffUrl" target="_blank" rel="noopener">Hubungi Staf Venue</x-poin.button>
    @endif
</main>
@endsection

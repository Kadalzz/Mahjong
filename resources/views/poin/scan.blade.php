@extends('layouts.poin')

@section('title', 'Scan Meja')

@section('content')
<main class="pn-page pn-page--gap-3-5" data-poin-scanner data-table-url="{{ url('/meja') }}" data-initial="{{ $unknown ? 'unknown' : 'camera' }}">
    <x-poin.app-bar title="Scan Meja" :back="route('poin.games')" profile="#" />
    <p class="t-kicker text-center">MAIN HANYA DI MEJA VENUE</p>
    <x-poin.ornament />

    <div class="pn-scan-state" data-poin-scan-state="camera" @if($unknown) hidden @endif>
        <div class="pn-viewfinder">
            <x-poin.qr-hint />
            <video class="pn-viewfinder__video" playsinline muted data-poin-video></video>
            <img class="pn-viewfinder__brackets" src="{{ asset('images/poin/parts/corner-brackets.svg') }}" alt="">
            <span class="pn-viewfinder__line"></span>
        </div>
        <p class="pn-scan-title t-title-md text-center">Arahkan kamera ke QR di meja</p>
        <p class="pn-subtle t-body text-center">Kamu otomatis masuk ke lobby meja itu. Poin hanya dihitung untuk permainan di venue.</p>
    </div>

    <div class="pn-scan-state" data-poin-scan-state="denied" hidden>
        <div class="pn-loader__center"><x-poin.state-message icon="camera-off" title="Izin kamera ditolak" desc="Aktifkan izin kamera di pengaturan untuk memindai QR meja." /></div>
        <div class="pn-stack">
            <x-poin.button data-poin-scan-retry>Buka Pengaturan</x-poin.button>
            <x-poin.button kind="ghost" data-poin-scan-code>Masukkan Kode Meja</x-poin.button>
        </div>
    </div>

    <div class="pn-scan-state" data-poin-scan-state="unknown" @unless($unknown) hidden @endunless>
        <div class="pn-loader__center"><x-poin.state-message icon="alert" title="QR tidak dikenali" desc="Pastikan kamu memindai QR resmi di meja venue." /></div>
        <div class="pn-stack">
            <x-poin.button data-poin-scan-retry>Scan Ulang</x-poin.button>
            <x-poin.button kind="ghost" data-poin-scan-code>Masukkan Kode Meja</x-poin.button>
        </div>
    </div>

    <form class="pn-scan-state" data-poin-scan-state="code" data-poin-code-form hidden novalidate>
        <div class="pn-loader__center pn-loader__center--form">
            <x-poin.input label="KODE MEJA *" name="code" placeholder="mis. A1B2C3D4" maxlength="12" autocomplete="off" />
        </div>
        <div class="pn-stack">
            <x-poin.button type="submit">Lanjut</x-poin.button>
            <x-poin.button kind="ghost" data-poin-scan-retry>Scan Ulang</x-poin.button>
        </div>
    </form>
</main>
@endsection

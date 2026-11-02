@props(['url', 'errorTitle' => 'Gagal memuat', 'errorDesc' => 'Server sedang bermasalah. Coba lagi beberapa saat.', 'offlineDesc' => 'Periksa internetmu, lalu coba lagi.', 'notFound' => null, 'secondaryHref' => null, 'secondaryLabel' => null])
<div {{ $attributes->class(['pn-loader']) }} data-poin-loader data-url="{{ $url }}" @if($notFound) data-not-found="{{ $notFound }}" @endif>
    <div class="pn-loader__state" data-poin-state="loading">{{ $loading }}</div>
    <div class="pn-loader__state" data-poin-state="offline" hidden>
        <div class="pn-loader__center"><x-poin.state-message title="Tidak ada koneksi" :desc="$offlineDesc" /></div>
        <x-poin.button data-poin-retry>Coba Lagi</x-poin.button>
        @if($secondaryHref)<x-poin.button kind="ghost" :href="$secondaryHref">{{ $secondaryLabel }}</x-poin.button>@endif
    </div>
    <div class="pn-loader__state" data-poin-state="error" hidden>
        <div class="pn-loader__center"><x-poin.state-message icon="alert" :title="$errorTitle" :desc="$errorDesc" /></div>
        <x-poin.button data-poin-retry>Coba Lagi</x-poin.button>
        @if($secondaryHref)<x-poin.button kind="ghost" :href="$secondaryHref">{{ $secondaryLabel }}</x-poin.button>@endif
    </div>
    <div class="pn-loader__state" data-poin-state="content" hidden></div>
</div>

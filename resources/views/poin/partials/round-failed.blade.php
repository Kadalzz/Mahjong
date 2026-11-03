<section class="pn-flow__step pn-page--gap-3-5" data-poin-step="failed" hidden>
    @unless($bare ?? false)
        <header class="pn-app-bar">
            <x-poin.icon-button label="Kembali" data-poin-step-back />
            <h1 class="t-title-lg">Poin Menang</h1>
            <a href="#" aria-label="Profil" class="pn-icon-link"><x-poin.avatar size="40" /></a>
        </header>
        <x-poin.ornament />
        <div class="flex justify-center"><x-poin.badge type="outline">RONDE {{ $round->number }}</x-poin.badge></div>
    @endunless
    <div class="pn-loader__center">
        <x-poin.state-message icon="alert" title="Poin belum tersimpan" desc="Koneksi terputus saat menyimpan. Coba simpan lagi." />
    </div>
    <x-poin.button data-poin-action-retry>Coba Simpan Lagi</x-poin.button>
    <form method="POST" action="{{ route('poin.round.stop', $game) }}">
        @csrf
        <x-poin.button type="submit" kind="ghost">Berhenti</x-poin.button>
    </form>
</section>

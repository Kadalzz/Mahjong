@php
    $labels = ['available' => 'Tersedia', 'playing' => 'Sedang dimainkan', 'full' => 'Penuh', 'unavailable' => 'Tidak tersedia'];
    $canJoin = $status === 'available';
@endphp
<div class="pn-detected">
    <x-poin.badge type="auto">QR TERBACA</x-poin.badge>
    <p class="t-display-hero whitespace-nowrap">{{ $table->displayName() }}</p>
    <p class="pn-muted t-body text-center">Meja otomatis terpilih sesuai QR yang kamu scan.</p>
    <x-poin.table-positions :you="$youSeat" :spin="false" class="pn-table-map--detected" />
</div>
<div class="pn-info-rows t-body">
    <div class="pn-info-rows__row"><span class="pn-muted">Status meja</span><span class="pn-info-rows__value t-body-bold">{{ $labels[$status] }}</span></div>
    <div class="pn-info-rows__row"><span class="pn-muted">Pemain di lobby</span><span class="pn-info-rows__value t-body-bold">{{ $count }} dari {{ \App\Models\Game::SEATS }}</span></div>
    <div class="pn-info-rows__row"><span class="pn-muted">Mode</span><span class="pn-info-rows__value t-body-bold">AUTO</span></div>
</div>
<span class="pn-page__spacer"></span>
<form method="POST" action="{{ route('poin.table.join', $table->qr_code) }}">
    @csrf
    <x-poin.button type="submit" :disabled="!$canJoin">Masuk Lobby {{ $table->displayName() }}</x-poin.button>
</form>
<x-poin.button kind="ghost" :href="route('poin.scan')">Scan Ulang</x-poin.button>

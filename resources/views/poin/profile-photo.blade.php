@extends('layouts.poin')

@section('title', 'Ubah Foto Profil')

@section('content')
<main class="pn-page">
    <form class="pn-page__form" method="POST" action="{{ route('poin.profile.photo.update') }}" enctype="multipart/form-data" novalidate
        data-poin-form data-poin-photo data-busy-label="Menyimpan…" data-retry-label="Coba Unggah Lagi" data-connection-error="Foto gagal diunggah. Periksa koneksi lalu coba lagi.">
        @csrf
        <x-poin.app-bar title="Ubah Foto Profil" :back="route('poin.profile')" />
        <x-poin.status-banner tone="error" data-poin-banner hidden>Foto gagal diunggah. Periksa koneksi lalu coba lagi.</x-poin.status-banner>
        @if($removed)
            <x-poin.status-banner tone="success" data-poin-removed>
                Foto profil dihapus.
                <x-slot:action>
                    <button type="submit" form="pn-photo-restore" class="pn-banner__action t-label-strong">Urungkan</button>
                </x-slot:action>
            </x-poin.status-banner>
        @endif
        <div class="pn-photo-preview">
            <x-poin.avatar size="150" :src="$player->avatarUrl()" data-poin-photo-preview />
            <p class="pn-muted t-body-sm text-center">Gunakan foto wajah yang jelas. Maksimal 5 MB, format JPG atau PNG.</p>
        </div>
        <p class="t-kicker">PILIH SUMBER</p>
        <input id="pn-photo-camera" class="pn-visually-hidden" type="file" name="foto" accept="image/jpeg,image/png" capture="user" data-poin-photo-input>
        <x-poin.menu-row icon="camera" for="pn-photo-camera">Ambil Foto</x-poin.menu-row>
        <input id="pn-photo-gallery" class="pn-visually-hidden" type="file" name="foto" accept="image/jpeg,image/png" data-poin-photo-input>
        <x-poin.menu-row icon="image" for="pn-photo-gallery">Pilih dari Galeri</x-poin.menu-row>
        <x-poin.menu-row icon="trash" type="submit" form="pn-photo-remove">Hapus Foto</x-poin.menu-row>
        <span class="pn-page__spacer"></span>
        <x-poin.submit-button>Simpan</x-poin.submit-button>
    </form>
    <form id="pn-photo-remove" method="POST" action="{{ route('poin.profile.photo.remove') }}" hidden>@csrf</form>
    <form id="pn-photo-restore" method="POST" action="{{ route('poin.profile.photo.restore') }}" hidden>@csrf</form>
</main>
@endsection

@extends('layouts.poin')

@section('title', 'Ubah Username')

@section('content')
<main class="pn-page">
    <form class="pn-page__form" method="POST" action="{{ route('poin.profile.username') }}" novalidate data-poin-form data-busy-label="Menyimpan…">
        @csrf
        <x-poin.app-bar title="Ubah Username" :back="route('poin.profile')" />
        <x-poin.status-banner tone="error" data-poin-banner hidden>Tidak terhubung ke server. Coba lagi.</x-poin.status-banner>
        <p class="pn-muted t-body">Username tampil di leaderboard dan dilihat semua pemain.</p>
        <x-poin.input label="USERNAME BARU" name="nickname" :placeholder="$player->nickname" autocomplete="username" :maxlength="\App\Models\Player::NICKNAME_MAX" :value="old('nickname')" :error="$errors->first('nickname') ?: $errors->first('nickname_key')" />
        <p class="pn-faint t-caption">Maksimal {{ \App\Models\Player::NICKNAME_MAX }} karakter, tanpa tanda @.</p>
        <span class="pn-page__spacer"></span>
        <x-poin.submit-button>Simpan</x-poin.submit-button>
    </form>
</main>
@endsection

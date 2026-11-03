@extends('layouts.poin')

@section('title', 'Ubah Sandi')

@section('content')
<main class="pn-page">
    <form class="pn-page__form" method="POST" action="{{ route('poin.profile.password') }}" novalidate data-poin-form data-poin-password-rules data-busy-label="Menyimpan…">
        @csrf
        <x-poin.app-bar title="Ubah Sandi" :back="route('poin.profile')" />
        <x-poin.status-banner tone="error" data-poin-banner hidden>Tidak terhubung ke server. Coba lagi.</x-poin.status-banner>
        <p class="pn-muted t-body">Masukkan sandi lama, lalu buat sandi baru yang sulit ditebak.</p>
        <x-poin.input label="SANDI LAMA" name="sandi_lama" type="password" placeholder="••••••••" autocomplete="current-password" maxlength="100" :error="$errors->first('sandi_lama')" />
        <x-poin.input label="SANDI BARU" name="sandi_baru" type="password" placeholder="••••••••••" autocomplete="new-password" maxlength="100" :error="$errors->first('sandi_baru')" />
        <x-poin.input label="ULANGI SANDI BARU" name="ulangi_sandi" type="password" placeholder="••••••••••" autocomplete="new-password" maxlength="100" :error="$errors->first('ulangi_sandi')" />
        <ul class="pn-checklist">
            @foreach(['length' => 'Minimal 8 karakter', 'mix' => 'Mengandung huruf dan angka', 'upper' => 'Minimal satu huruf besar', 'match' => 'Sama dengan ulangi sandi'] as $rule => $text)
                <li class="pn-checklist__item" data-poin-rule="{{ $rule }}"><x-poin.icon name="check" size="16" tone="white" /><span class="t-caption">{{ $text }}</span></li>
            @endforeach
        </ul>
        <span class="pn-page__spacer"></span>
        <x-poin.submit-button>Simpan</x-poin.submit-button>
    </form>
</main>
@endsection

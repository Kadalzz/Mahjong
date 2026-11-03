@extends('layouts.poin')

@section('title', 'Profil')

@section('content')
<main @class(['pn-page pn-page--gap-2-5 pn-profile', 'has-banner' => $status])>
    <x-poin.app-bar title="Profil" :back="route('poin.games')" />
    @if($status)
        <x-poin.status-banner tone="success">{{ $status }}</x-poin.status-banner>
    @endif
    <x-poin.loader :url="route('poin.profile.content')" class="pn-page__body" error-title="Profil gagal dimuat" offline-desc="Data profilmu belum bisa dimuat. Sambungkan internet lalu coba lagi.">
        <x-slot:loading>
            <div class="pn-profile-head pn-profile-head--skeleton">
                <x-poin.skeleton class="pn-skeleton--profile pn-skeleton--profile-photo" />
                <x-poin.skeleton class="pn-skeleton--profile pn-skeleton--profile-name" />
                <x-poin.skeleton class="pn-skeleton--profile pn-skeleton--profile-handle" />
            </div>
            <div class="pn-profile-stats">
                @foreach([1, 2, 3] as $stat)
                    <x-poin.skeleton class="pn-skeleton--profile pn-skeleton--profile-stat" />
                @endforeach
            </div>
            @foreach([1, 2, 3] as $row)
                <x-poin.skeleton class="pn-skeleton--profile pn-skeleton--profile-row" />
            @endforeach
            <span class="pn-page__spacer"></span>
            <x-poin.button kind="ghost" disabled>Keluar</x-poin.button>
        </x-slot:loading>
    </x-poin.loader>
</main>
@push('dialogs')
    <x-poin.dialog-overlay id="pn-logout-dialog" title="Keluar dari akun?" desc="Kamu perlu masuk lagi untuk bermain dan mengumpulkan poin." confirm="Ya, keluar" :action="route('poin.logout')" />
@endpush
@endsection

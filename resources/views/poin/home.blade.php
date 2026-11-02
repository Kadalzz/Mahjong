@extends('layouts.poin')

@section('title', 'Hóng Zhōng Mahjong')

@section('content')
<main class="pn-screen pn-stack">
    <x-poin.brand-lockup class="w-full" />
    <p class="t-h3 text-center">{{ $player->nickname }}</p>
    <form method="POST" action="{{ route('poin.logout') }}">
        @csrf
        <x-poin.button kind="ghost" type="submit">Keluar</x-poin.button>
    </form>
</main>
@endsection

@props(['title', 'subtitle', 'active', 'action', 'submit', 'switchText', 'switchLink', 'switchHref', 'watermark' => 'login'])
<main class="pn-auth pn-auth--{{ $watermark }}">
    <img class="pn-watermark" src="{{ asset('images/poin/illustrations/watermark.svg') }}" alt="">
    <x-poin.brand-lockup class="w-full" />
    <h1 class="pn-auth__title t-h1">{{ $title }}</h1>
    <p class="pn-auth__subtitle t-body">{{ $subtitle }}</p>
    <x-poin.segmented>
        <x-poin.tab :selected="$active === 'login'" :href="route('poin.login')">Masuk</x-poin.tab>
        <x-poin.tab :selected="$active === 'register'" :href="route('poin.register')">Daftar</x-poin.tab>
    </x-poin.segmented>
    <form class="pn-auth__form" method="POST" action="{{ $action }}" data-poin-form novalidate>
        @csrf
        <x-poin.status-banner tone="error" data-poin-banner hidden>Tidak terhubung ke server. Coba lagi.</x-poin.status-banner>
        {{ $slot }}
        <span class="pn-auth__spacer"></span>
        <x-poin.submit-button>{{ $submit }}</x-poin.submit-button>
        <a class="pn-auth__switch t-body" href="{{ $switchHref }}">{{ $switchText }} {{ $switchLink }}</a>
    </form>
</main>

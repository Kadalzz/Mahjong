@props(['title', 'back' => null, 'profile' => null, 'avatar' => null])
<header {{ $attributes->class(['pn-app-bar']) }}>
    @if($back)
        <x-poin.icon-button :href="$back" label="Kembali" />
    @else
        <span class="pn-app-bar__spacer"></span>
    @endif
    <h1 class="t-title-lg">{{ $title }}</h1>
    @if($profile)
        <a href="{{ $profile }}" aria-label="Profil" class="pn-icon-link"><x-poin.avatar size="40" :src="$avatar" /></a>
    @else
        <span class="pn-app-bar__spacer"></span>
    @endif
</header>

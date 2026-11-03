@props(['title', 'back' => null, 'profile' => false])
<header {{ $attributes->class(['pn-app-bar']) }}>
    @if($back)
        <x-poin.icon-button :href="$back" label="Kembali" />
    @else
        <span class="pn-app-bar__spacer"></span>
    @endif
    <h1 class="t-title-lg">{{ $title }}</h1>
    @if($profile)
        <x-poin.profile-link />
    @else
        <span class="pn-app-bar__spacer"></span>
    @endif
</header>

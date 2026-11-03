<div class="pn-profile-head">
    <div class="pn-profile-photo">
        <x-poin.avatar size="112" :src="$player->avatarUrl()" />
        <a href="{{ route('poin.profile.photo') }}" class="pn-profile-photo__edit" aria-label="Ubah foto profil"><x-poin.icon name="pencil" size="18" tone="ink" /></a>
    </div>
    <p class="pn-profile-head__name t-h2">Kamu</p>
    <p class="pn-muted pn-truncate t-body-sm">{{ '@' . $player->nickname }}</p>
</div>
<div class="pn-profile-stats">
    @foreach(['POIN' => $points, 'MAIN' => $plays, 'MENANG' => $wins] as $label => $value)
        <div class="pn-profile-stat">
            <p class="t-h3">{{ $value }}</p>
            <p class="pn-faint t-caption-bold">{{ $label }}</p>
        </div>
    @endforeach
</div>
<p class="pn-profile__kicker t-kicker">AKUN</p>
<x-poin.menu-row icon="camera" :href="route('poin.profile.photo')">Ubah Foto Profil</x-poin.menu-row>
<x-poin.menu-row icon="pencil" :href="route('poin.profile.username')">Ubah Username</x-poin.menu-row>
<x-poin.menu-row icon="lock" :href="route('poin.profile.password')">Ubah Sandi</x-poin.menu-row>
<x-poin.menu-row icon="info" href="#">Bantuan</x-poin.menu-row>
<span class="pn-page__spacer"></span>
<form method="POST" action="{{ route('poin.logout') }}">
    @csrf
    <x-poin.button kind="ghost" type="submit">Keluar</x-poin.button>
</form>

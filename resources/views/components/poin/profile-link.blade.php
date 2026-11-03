@php $player = auth('player')->user(); @endphp
<a href="{{ route('poin.profile') }}" aria-label="Profil" {{ $attributes->class(['pn-icon-link']) }}><x-poin.avatar size="40" :src="$player?->avatarUrl()" /></a>

@props(['size' => null, 'hidden' => false])
<span {{ $attributes->class(['pn-spinner', 'pn-spinner--button' => $size === 'button']) }} aria-hidden="true" @if($hidden) hidden @endif>
    <img class="pn-spinner__layer" src="{{ asset('images/poin/parts/spinner-track.svg') }}" alt="">
    <img class="pn-spinner__layer pn-spinner__ring" src="{{ asset('images/poin/parts/spinner-ring.svg') }}" alt="">
</span>

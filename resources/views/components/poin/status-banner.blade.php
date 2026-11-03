@props(['tone' => 'error', 'action' => null])
<div role="status" {{ $attributes->class(['pn-banner', "pn-banner--{$tone}"]) }}>
    <img class="pn-banner__glyph" src="{{ asset('images/poin/parts/' . ($tone === 'success' ? 'status-success' : 'status-alert') . '.svg') }}" alt="">
    <p class="pn-banner__message t-body-sm">{{ $slot }}</p>
    {{ $action }}
</div>

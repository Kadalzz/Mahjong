@props(['loading' => false])
<div class="pn-submit" data-poin-submit>
    <button type="submit" @disabled($loading) {{ $attributes->class(['pn-btn t-button pn-btn--primary', 'is-disabled' => $loading]) }}>
        <span data-poin-label>{{ $slot }}</span>
    </button>
    <x-poin.spinner size="button" class="pn-submit__spinner" :hidden="!$loading" />
</div>

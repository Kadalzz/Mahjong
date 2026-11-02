@props(['state' => null, 'type' => 'button', 'label' => 'Konfirmasi'])
<button type="{{ $type }}" aria-label="{{ $label }}" {{ $attributes->class(['pn-confirm', "is-{$state}" => $state]) }}>
    <span class="pn-confirm__disc"><x-poin.icon name="check" tone="cream" /></span>
</button>

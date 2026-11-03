@props(['tone' => 'win', 'label', 'value'])
<span {{ $attributes->class(['pn-score-chip', "pn-score-chip--{$tone}"]) }}>
    <span class="pn-truncate t-caption">{{ $label }}</span>
    <span class="t-h5">{{ $value }}</span>
</span>

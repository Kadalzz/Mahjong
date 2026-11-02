@props(['name', 'size' => 24, 'tone' => null])
@php
    $tone ??= $name === 'user' ? 'cream-dark' : 'gold-light';
    $url = asset("images/poin/icons/{$name}.svg");
@endphp
<span {{ $attributes->class(["pn-icon pn-icon--{$size} pn-tone-{$tone}"]) }} style="--icon: url('{{ $url }}')" aria-hidden="true"></span>

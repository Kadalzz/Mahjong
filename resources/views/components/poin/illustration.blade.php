@props(['name'])
@if(in_array($name, ['win-badge', 'lose-badge'], true))
    <span {{ $attributes->class(['pn-medal', $name === 'win-badge' ? 'pn-medal--win' : 'pn-medal--lose']) }}>
        <img class="pn-medal__image" src="{{ asset("images/poin/illustrations/{$name}.svg") }}" alt="">
    </span>
@else
    <img {{ $attributes->class(['pn-illustration']) }} src="{{ asset("images/poin/illustrations/{$name}.svg") }}" alt="">
@endif

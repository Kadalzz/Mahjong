@php
    $dots = [3, 8, 13, 18, 23, 28, 33, 38];
    $ribbons = [5, 10, 15, 20, 25, 30, 35, 40];
@endphp
<div {{ $attributes->class(['pn-confetti']) }} aria-hidden="true">
    @foreach(range(1, 40) as $piece)
        <span class="pn-confetti__piece pn-confetti__piece--{{ $piece }}">
            @if(in_array($piece, $dots, true))
                <img src="{{ asset("images/poin/parts/confetti-{$piece}.svg") }}" alt="">
            @elseif(in_array($piece, $ribbons, true))
                <span class="pn-confetti__bit"><img src="{{ asset("images/poin/parts/confetti-{$piece}.svg") }}" alt=""></span>
            @else
                <span class="pn-confetti__bit"></span>
            @endif
        </span>
    @endforeach
</div>

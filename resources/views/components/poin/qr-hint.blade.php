@php
    $cells = [[0, 0], [0, 1], [0, 2], [0, 4], [1, 0], [1, 2], [1, 3], [2, 0], [2, 1], [2, 2], [2, 4], [3, 1], [3, 3], [3, 4], [4, 0], [4, 2], [4, 3]];
@endphp
<span class="pn-qr-hint" aria-hidden="true">
    @foreach($cells as [$row, $column])
        <span class="pn-qr-hint__cell" style="grid-row: {{ $row + 1 }}; grid-column: {{ $column + 1 }}"></span>
    @endforeach
</span>

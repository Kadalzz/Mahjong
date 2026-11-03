<form method="POST" action="{{ route('poin.round.continue', $game) }}" data-poin-action data-poin-busy-label="Menyimpan…">
    @csrf
    <x-poin.button type="submit">Lanjutkan Permainan</x-poin.button>
</form>
@include('poin.partials.stop-session')

<form method="POST" action="{{ route('poin.round.continue', $game) }}" data-poin-action data-poin-busy-label="Menyimpan…">
    @csrf
    <x-poin.button type="submit">Lanjutkan Permainan</x-poin.button>
</form>
<form method="POST" action="{{ route('poin.round.stop', $game) }}">
    @csrf
    <x-poin.button type="submit" kind="ghost">Berhenti</x-poin.button>
</form>

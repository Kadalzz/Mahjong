<x-poin.button kind="ghost" data-poin-dialog-open="pn-stop-dialog">Berhenti</x-poin.button>
@once
    @push('dialogs')
        <x-poin.dialog-overlay id="pn-stop-dialog" title="Akhiri sesi bermain?" desc="Poinmu tetap tersimpan di leaderboard. Kamu bisa mulai lagi kapan saja di meja venue." confirm="Ya, akhiri sesi" :action="route('poin.round.stop', $game)" />
    @endpush
@endonce

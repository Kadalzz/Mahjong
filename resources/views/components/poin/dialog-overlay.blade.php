@props(['id', 'title', 'desc', 'confirm', 'action'])
<div id="{{ $id }}" {{ $attributes->class(['pn-dialog-overlay']) }} data-poin-dialog hidden>
    <x-poin.confirm-dialog :title="$title" :desc="$desc">
        <form method="POST" action="{{ $action }}">
            @csrf
            <x-poin.button type="submit" compact>{{ $confirm }}</x-poin.button>
        </form>
        <x-poin.button kind="ghost" compact data-poin-dialog-close>Batal</x-poin.button>
    </x-poin.confirm-dialog>
</div>

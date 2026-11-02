@props(['title', 'desc', 'confirm' => 'Ya, lanjutkan', 'cancel' => 'Batal'])
<div role="alertdialog" aria-label="{{ $title }}" {{ $attributes->class(['pn-dialog']) }}>
    <span class="pn-dialog__icon"><img class="pn-dialog__glyph" src="{{ asset('images/poin/parts/dialog-alert.svg') }}" alt=""></span>
    <p class="pn-dialog__title t-h4">{{ $title }}</p>
    <p class="pn-dialog__desc t-body">{{ $desc }}</p>
    @if($slot->isEmpty())
        <x-poin.button compact>{{ $confirm }}</x-poin.button>
        <x-poin.button kind="ghost" compact>{{ $cancel }}</x-poin.button>
    @else
        {{ $slot }}
    @endif
</div>

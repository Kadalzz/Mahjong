@props(['label', 'name', 'type' => 'text', 'value' => null, 'placeholder' => null, 'error' => null, 'state' => null, 'disabled' => false])
@php
    $id = $attributes->get('id', 'field-' . $name);
    $hasError = $error || $state === 'error';
@endphp
<div @class(['pn-field', 'is-error' => $hasError, 'is-focus' => $state === 'focus', 'is-disabled' => $disabled])>
    <label for="{{ $id }}" class="pn-field__label t-kicker">{{ $label }}</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" placeholder="{{ $placeholder }}" @disabled($disabled) @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif {{ $attributes->except('id')->class(['pn-field__input t-body-lg']) }}>
    @if($error)
        <p id="{{ $id }}-error" class="pn-field__helper t-body-sm">{{ $error }}</p>
    @endif
</div>

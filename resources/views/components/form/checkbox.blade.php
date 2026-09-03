@props([
    'name',
    'label',
    'checked' => false,
    'hint' => null,
    'switch' => false,
])

@php
    $id = $attributes->get('id', $name);
    $isChecked = (bool) old($name, $checked);
    $invalid = $errors->has($name);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'mb-3']) }}>
    {{-- Hidden sibling so an unchecked box still posts a value. --}}
    <input type="hidden" name="{{ $name }}" value="0">

    <div @class(['form-check', 'form-switch' => $switch])>
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $id }}"
            value="1"
            @checked($isChecked)
            {{ $attributes->except(['class', 'id'])->merge(['class' => 'form-check-input' . ($invalid ? ' is-invalid' : '')]) }}
        >
        <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>

        @error($name)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    @if ($hint)
        <small class="form-hint">{{ $hint }}</small>
    @endif
</div>

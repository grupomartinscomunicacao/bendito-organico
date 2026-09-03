@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'icon' => null,
])

@php
    $id = $attributes->get('id', $name);
    // Dotted names ("address.city") need bracket notation for old()/errors.
    $errorKey = $name;
    $current = old($errorKey, $value);
    $invalid = $errors->has($errorKey);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'mb-3']) }}>
    @if ($label)
        <label class="form-label" for="{{ $id }}">
            {{ $label }}
            @if ($required)<span class="required-mark" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <div @class(['input-group' => $icon])>
        @if ($icon)
            <span class="input-group-text"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></span>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $type === 'password' ? '' : $current }}"
            @if ($required) required @endif
            @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except(['class', 'id'])->merge(['class' => 'form-control' . ($invalid ? ' is-invalid' : '')]) }}
        >

        @error($errorKey)
            <div class="invalid-feedback" id="{{ $id }}-error">{{ $message }}</div>
        @enderror
    </div>

    @if ($hint)
        <small class="form-hint">{{ $hint }}</small>
    @endif
</div>

@props([
    'name',
    'label' => null,
    'value' => null,
    'hint' => null,
    'rows' => 4,
    'required' => false,
])

@php
    $id = $attributes->get('id', $name);
    $current = old($name, $value);
    $invalid = $errors->has($name);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'mb-3']) }}>
    @if ($label)
        <label class="form-label" for="{{ $id }}">
            {{ $label }}
            @if ($required)<span class="required-mark" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'form-control' . ($invalid ? ' is-invalid' : '')]) }}
    >{{ $current }}</textarea>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    @if ($hint)
        <small class="form-hint">{{ $hint }}</small>
    @endif
</div>

@props([
    'product',
    'value' => 1,
    'name' => 'quantity',
])

@php
    $step = $product->unit->step();
    $max = $product->maxOrderableQuantity();
@endphp

<div class="quantity-stepper" data-quantity-stepper>
    <button type="button" data-step="down" aria-label="Diminuir quantidade">&minus;</button>

    <input
        type="number"
        name="{{ $name }}"
        inputmode="decimal"
        value="{{ $value }}"
        step="{{ $step }}"
        min="{{ $step }}"
        max="{{ $max }}"
        data-step="{{ $step }}"
        data-min="{{ $step }}"
        data-max="{{ $max }}"
        data-unit-price="{{ $product->price }}"
        aria-label="Quantidade em {{ $product->unit->abbreviation() }}"
        required
    >

    <button type="button" data-step="up" aria-label="Aumentar quantidade">+</button>
</div>

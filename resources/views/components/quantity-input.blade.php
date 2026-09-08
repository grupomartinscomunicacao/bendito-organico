@props([
    'product',
    'value' => null,
    'name' => 'quantity',
])

@php
    $step = $product->unit->step();
    $max = $product->maxOrderableQuantity();

    // O piso é a quantidade que alcança o pedido mínimo, não o passo da
    // unidade: deixar o cliente descer abaixo dela só produziria um erro no
    // envio. O servidor confere de novo em CreateOrder — isto aqui é para o
    // seletor nunca oferecer um valor que não dá para comprar.
    $min = min($product->minimumOrderQuantity(), $max);

    // Sem valor explícito, abre já no mínimo comprável.
    $value = $value === null ? $min : max($min, (float) $value);
@endphp

<div class="quantity-stepper" data-quantity-stepper>
    <button type="button" data-step="down" aria-label="Diminuir quantidade">&minus;</button>

    <input
        type="number"
        name="{{ $name }}"
        inputmode="decimal"
        value="{{ \App\Support\Money::quantity($value) }}"
        step="{{ $step }}"
        min="{{ $min }}"
        max="{{ $max }}"
        data-step="{{ $step }}"
        data-min="{{ $min }}"
        data-max="{{ $max }}"
        data-unit-price="{{ $product->price }}"
        aria-label="Quantidade em {{ $product->unit->abbreviation() }}"
        required
    >

    <button type="button" data-step="up" aria-label="Aumentar quantidade">+</button>
</div>

@props([
    'value',
    'unit' => null,
    'compareAt' => null,
])

<span {{ $attributes->merge(['class' => 'price']) }}>
    <span class="price__value">{{ \App\Support\Money::brl($value) }}</span>

    @if ($unit)
        <span class="price__unit">/ {{ $unit->abbreviation() }}</span>
    @endif

    @if ($compareAt)
        <span class="price__compare">{{ \App\Support\Money::brl($compareAt) }}</span>
    @endif
</span>

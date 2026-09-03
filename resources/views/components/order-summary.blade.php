@props([
    'items',
    'subtotal',
    'deliveryFee' => 0,
    'total',
    'title' => 'Resumo do pedido',
    'sticky' => false,
])

<aside @class(['order-summary', 'order-summary--sticky' => $sticky])>
    <div class="order-summary__head">
        <h2>{{ $title }}</h2>
    </div>

    <div class="order-summary__body">
        @foreach ($items as $item)
            <div class="order-summary__item">
                <img
                    src="{{ $item['image'] }}"
                    alt="{{ $item['name'] }}"
                    class="order-summary__thumb"
                    loading="lazy"
                    width="64"
                    height="64"
                >
                <div class="flex-grow-1">
                    <p class="order-summary__name">{{ $item['name'] }}</p>
                    <p class="order-summary__meta mb-0">
                        {{ $item['quantity'] }} {{ $item['unit'] }} ×
                        {{ \App\Support\Money::brl($item['unit_price']) }}
                    </p>
                </div>
                <strong class="text-nowrap">{{ \App\Support\Money::brl($item['subtotal']) }}</strong>
            </div>
        @endforeach

        <div class="order-summary__lines">
            <div>
                <span>Subtotal</span>
                <span>{{ \App\Support\Money::brl($subtotal) }}</span>
            </div>
            <div>
                <span>Entrega</span>
                <span>
                    @if ((float) $deliveryFee > 0)
                        {{ \App\Support\Money::brl($deliveryFee) }}
                    @else
                        <span class="text-success fw-semibold">A combinar</span>
                    @endif
                </span>
            </div>
        </div>

        <div class="order-summary__total">
            <span>Total</span>
            <strong>{{ \App\Support\Money::brl($total) }}</strong>
        </div>

        {{ $slot }}
    </div>
</aside>

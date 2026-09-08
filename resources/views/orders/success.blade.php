@extends('layouts.app')

@section('title', 'Pedido realizado com sucesso')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')

    @include('orders.partials.hero', [
        'order' => $order,
        'icon' => 'check-lg',
        'heading' => 'Pedido realizado com sucesso!',
        'lead' => 'Recebemos a confirmação do seu pagamento e já começamos a separar seus orgânicos.',
    ])

    <div class="container py-4 py-lg-5">
        <div class="row g-4 justify-content-center">

            <div class="col-lg-7 col-xl-6">

                <section class="checkout-panel">
                    <header class="checkout-panel__head">
                        <span class="step-marker"><i class="bi bi-bag-check" aria-hidden="true"></i></span>
                        <div>
                            <h2>O que você pediu</h2>
                            <p>Resumo do que vai chegar até você.</p>
                        </div>
                    </header>

                    @foreach ($order->items as $item)
                        <div class="order-summary__item">
                            <img
                                src="{{ $item->image_url }}"
                                alt="{{ $item->product_name }}"
                                class="order-summary__thumb"
                                width="64"
                                height="64"
                                loading="lazy"
                            >
                            <div class="flex-grow-1">
                                <p class="order-summary__name">{{ $item->product_name }}</p>
                                <p class="order-summary__meta mb-0">
                                    {{ $item->formatted_quantity }} {{ $item->product_unit->abbreviationFor((float) $item->quantity) }} ×
                                    {{ \App\Support\Money::brl($item->unit_price) }}
                                </p>
                            </div>
                            <strong class="text-nowrap">{{ \App\Support\Money::brl($item->subtotal) }}</strong>
                        </div>
                    @endforeach

                    <div class="order-summary__total">
                        <span>Total pago</span>
                        <strong>{{ \App\Support\Money::brl($order->total) }}</strong>
                    </div>
                </section>

                <section class="checkout-panel">
                    <header class="checkout-panel__head">
                        <span class="step-marker"><i class="bi bi-truck" aria-hidden="true"></i></span>
                        <div>
                            <h2>Sobre a entrega</h2>
                            <p>Entramos em contato para combinar o horário.</p>
                        </div>
                    </header>

                    @if ($order->address)
                        <div class="info-grid mb-3">
                            <div style="grid-column: 1 / -1">
                                <p class="info-grid__label">Entregaremos em</p>
                                <p class="info-grid__value mb-0">
                                    {{ $order->address->line1 }}<br>
                                    <span class="fw-normal text-muted">{{ $order->address->line2 }}</span>
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="alert alert-info mb-0" role="status">
                        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                        <div>{{ config('bendito.checkout.delivery_notice') }}</div>
                    </div>
                </section>

                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                    <x-button href="{{ route('orders.show', $order) }}" variant="primary" size="lg" icon="signpost-split">
                        Acompanhar pedido
                    </x-button>
                    <x-button href="{{ route('products.index') }}" variant="outline-primary" size="lg" icon="basket2">
                        Continuar comprando
                    </x-button>
                </div>

                <p class="text-center text-muted mt-4 mb-0" style="font-size:.9375rem">
                    Enviamos uma cópia deste resumo para <strong>{{ $order->customer_email }}</strong>.
                    Para acompanhar depois, é só informar o seu telefone em
                    <a href="{{ route('orders.lookup') }}">Meu pedido</a>.
                </p>
            </div>
        </div>
    </div>

@endsection

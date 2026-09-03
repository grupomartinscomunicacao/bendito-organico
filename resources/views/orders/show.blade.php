@extends('layouts.app')

{{-- Sem o número no título: ele não é mostrado em lugar nenhum da página. --}}
@section('title', 'Meu pedido')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')

    @include('orders.partials.hero', [
        'order' => $order,
        'icon' => $order->isPaid() ? 'check-lg' : ($order->isCancelled() ? 'x-lg' : 'hourglass-split'),
        'heading' => $order->isPaid()
            ? 'Pagamento confirmado!'
            : ($order->isCancelled() ? 'Pedido cancelado' : 'Pedido registrado'),
        'lead' => $order->isPaid()
            ? 'Já estamos separando seus orgânicos. Avisamos quando sair para entrega.'
            : ($order->isCancelled()
                ? 'Este pedido foi cancelado. Se foi engano, fale com a gente.'
                : 'Estamos aguardando a confirmação do pagamento pelo Mercado Pago.'),
    ])

    <div class="container section-narrow">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8 col-xl-7">

                {{-- Só aparece quando ainda há algo a fazer. --}}
                @if ($order->isPayable())
                    <div class="checkout-panel pay-callout mb-3">
                        <div>
                            <h2 class="h6 mb-1">Seu pedido está reservado</h2>
                            <p class="mb-0">Conclua o pagamento para começarmos a separação.</p>
                        </div>

                        <form method="POST" action="{{ route('orders.pay', $order) }}" data-loading>
                            @csrf
                            <button type="submit" class="btn btn-accent btn-lg w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                <span class="btn-spinner spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <i class="bi bi-credit-card btn-label" aria-hidden="true"></i>
                                <span class="btn-label">Pagar agora</span>
                            </button>
                        </form>
                    </div>
                @endif

                {{-- O que foi pedido, quanto e por quanto. --}}
                <x-order-summary
                    title="Seu pedido"
                    :items="$order->items->map(fn ($item) => [
                        'name' => $item->product_name,
                        'image' => $item->image_url,
                        'quantity' => $item->formatted_quantity,
                        'unit' => $item->product_unit->abbreviation(),
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                    ])->all()"
                    :subtotal="$order->subtotal"
                    :delivery-fee="$order->delivery_fee"
                    :total="$order->total"
                />

                {{-- Entrega: só o essencial para o cliente conferir. --}}
                @if ($order->address)
                    <section class="checkout-panel mt-3">
                        <header class="checkout-panel__head">
                            <span class="step-marker"><i class="bi bi-truck" aria-hidden="true"></i></span>
                            <div>
                                <h2>Entrega</h2>
                                <p>{{ $order->customer_name }}</p>
                            </div>
                        </header>

                        <p class="order-address mb-0">
                            {{ $order->address->line1 }}<br>
                            <span class="text-muted">{{ $order->address->line2 }}</span>
                            @if ($order->address->reference)
                                <br><span class="text-muted">Referência: {{ $order->address->reference }}</span>
                            @endif
                        </p>

                        @if ($order->notes)
                            <p class="order-address mt-3 pt-3 border-top mb-0">
                                <span class="text-muted">Observações: {{ $order->notes }}</span>
                            </p>
                        @endif
                    </section>
                @endif

                <div class="d-grid gap-2 mt-3">
                    <a
                        href="https://wa.me/{{ config('bendito.contact.whatsapp') }}?text={{ urlencode('Olá! Tenho uma dúvida sobre o meu pedido.') }}"
                        class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center gap-2"
                        target="_blank"
                        rel="noopener"
                    >
                        <i class="bi bi-whatsapp" aria-hidden="true"></i>
                        Falar sobre este pedido
                    </a>
                </div>

            </div>
        </div>
    </div>

@endsection

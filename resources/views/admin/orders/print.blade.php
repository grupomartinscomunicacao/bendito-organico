@extends('layouts.print')

@section('title', 'Cartão do pedido '.$order->public_number)

@section('content')

    {{-- Screen-only toolbar; the print stylesheet removes it. --}}
    <div class="print-toolbar no-print">
        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Voltar ao pedido
        </a>

        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            Imprimir cartão
        </button>
    </div>

    <article class="order-card">

        {{-- Header: official logo + order number --}}
        <header class="order-card__header">
            <div class="d-flex align-items-center gap-3">
                <x-logo variant="light" class="order-card__logo" />
            </div>

            <div class="order-card__ref">
                <small>Pedido</small>
                <strong>{{ $order->public_number }}</strong>
            </div>
        </header>

        {{-- Ribbon: the at-a-glance facts for whoever picks the order --}}
        <div class="order-card__ribbon">
            <span>
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                {{ $order->created_at->format('d/m/Y H:i') }}
            </span>
            <span>
                <i class="bi bi-box-seam" aria-hidden="true"></i>
                {{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('item', $order->items->count()) }}
            </span>
            <span>
                <i class="bi bi-{{ $order->status->icon() }}" aria-hidden="true"></i>
                {{ $order->status->label() }}
            </span>
            <span>
                <i class="bi bi-{{ $order->payment_status->icon() }}" aria-hidden="true"></i>
                Pagamento: {{ $order->payment_status->label() }}
            </span>
        </div>

        <div class="order-card__body">

            {{-- Customer --}}
            <section class="order-card__section">
                <p class="order-card__section-title">Cliente</p>

                <div class="order-card__grid">
                    <div class="order-card__field">
                        <strong>{{ $order->customer_name }}</strong>
                        <span>Nome do cliente</span>
                    </div>
                    <div class="order-card__field">
                        <strong>{{ $order->formatted_phone }}</strong>
                        <span>Telefone / WhatsApp</span>
                    </div>
                    <div class="order-card__field">
                        <strong>{{ $order->customer_email }}</strong>
                        <span>E-mail</span>
                    </div>
                </div>
            </section>

            {{-- Delivery address --}}
            @if ($order->address)
                <section class="order-card__section">
                    <p class="order-card__section-title">Endereço de entrega</p>

                    <div class="order-card__grid">
                        <div class="order-card__field" style="grid-column: 1 / -1">
                            <strong>{{ $order->address->line1 }}</strong>
                            <span>{{ $order->address->district }} — {{ $order->address->city }}/{{ $order->address->state }} · CEP {{ $order->address->zip_code }}</span>
                        </div>

                        @if ($order->address->reference)
                            <div class="order-card__field" style="grid-column: 1 / -1">
                                <strong>{{ $order->address->reference }}</strong>
                                <span>Ponto de referência</span>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Items --}}
            <section class="order-card__section">
                <p class="order-card__section-title">Itens para separação</p>

                <table class="order-card__table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Qtd.</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product_name }}</strong>
                                    <span style="display:block;font-size:.8125rem;color:#7d8b80">
                                        {{ \App\Support\Money::brl($item->unit_price) }} por {{ $item->product_unit->abbreviation() }}
                                    </span>
                                </td>
                                <td style="white-space:nowrap;font-weight:700">
                                    {{ $item->formatted_quantity }} {{ $item->product_unit->abbreviation() }}
                                </td>
                                <td style="white-space:nowrap">{{ \App\Support\Money::brl($item->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="order-card__totals">
                    <div>
                        <span>Subtotal</span>
                        <span>{{ \App\Support\Money::brl($order->subtotal) }}</span>
                    </div>
                    <div>
                        <span>Entrega</span>
                        <span>{{ (float) $order->delivery_fee > 0 ? \App\Support\Money::brl($order->delivery_fee) : 'A combinar' }}</span>
                    </div>
                    <div class="is-total">
                        <span>Total</span>
                        <span>{{ \App\Support\Money::brl($order->total) }}</span>
                    </div>
                </div>
            </section>

            {{-- Notes --}}
            @if ($order->notes)
                <section class="order-card__section">
                    <p class="order-card__section-title">Observações do cliente</p>
                    <p class="order-card__note mb-0">{{ $order->notes }}</p>
                </section>
            @endif
        </div>

        {{-- Thanks --}}
        <footer class="order-card__thanks">
            <strong>Obrigado por escolher o {{ config('bendito.name') }}! 🌱</strong>
            <span>
                Colhido com cuidado para você. Qualquer coisa, fale com a gente:
                {{ config('bendito.contact.phone') }} · {{ config('bendito.contact.email') }}
            </span>
        </footer>

        <div class="order-card__cut">
            <i class="bi bi-scissors" aria-hidden="true"></i>
            Anexe este cartão à sacola do pedido {{ $order->public_number }}
        </div>
    </article>

@endsection

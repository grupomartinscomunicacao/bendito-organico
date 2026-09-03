@extends('layouts.admin')

@section('title', 'Pedido '.$order->public_number)

@section('content')

    <x-admin.page-head
        :title="$order->public_number"
        :subtitle="'Recebido em '.$order->created_at->translatedFormat('d \d\e F \d\e Y, \à\s H:i')"
    >
        <x-slot:actions>
            <x-button
                href="{{ route('admin.orders.print', $order) }}"
                variant="primary"
                icon="printer"
                target="_blank"
                rel="noopener"
            >
                Imprimir cartão
            </x-button>

            <x-button href="{{ route('orders.show', $order) }}" variant="outline-secondary" icon="box-arrow-up-right" target="_blank" rel="noopener">
                Ver como cliente
            </x-button>

            <x-button href="{{ route('admin.orders.index') }}" variant="outline-secondary" icon="arrow-left">
                Voltar
            </x-button>
        </x-slot:actions>
    </x-admin.page-head>

    <div class="row g-3">

        <div class="col-xl-8">

            {{-- Items --}}
            <x-admin.card title="Itens do pedido" flush>
                <div class="table-scroll">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width:3.5rem"></th>
                                <th>Produto</th>
                                <th>Preço unit.</th>
                                <th>Qtd.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>
                                        <img src="{{ $item->image_url }}" alt="" class="table-thumb" width="44" height="44" loading="lazy">
                                    </td>
                                    <td>
                                        @if ($item->product)
                                            <a href="{{ route('admin.products.edit', $item->product) }}" class="fw-semibold link-quiet">
                                                {{ $item->product_name }}
                                            </a>
                                        @else
                                            <span class="fw-semibold">{{ $item->product_name }}</span>
                                            <span class="badge-soft badge-soft--secondary ms-1">fora do catálogo</span>
                                        @endif
                                        <span class="d-block text-muted" style="font-size:.8125rem">/{{ $item->product_slug }}</span>
                                    </td>
                                    <td class="text-nowrap">{{ \App\Support\Money::brl($item->unit_price) }}</td>
                                    <td class="text-nowrap">
                                        {{ $item->formatted_quantity }} {{ $item->product_unit->abbreviation() }}
                                    </td>
                                    <td class="text-end fw-semibold text-nowrap">{{ \App\Support\Money::brl($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end text-muted">Subtotal</td>
                                <td class="text-end">{{ \App\Support\Money::brl($order->subtotal) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end text-muted">Entrega</td>
                                <td class="text-end">
                                    {{ (float) $order->delivery_fee > 0 ? \App\Support\Money::brl($order->delivery_fee) : 'A combinar' }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold fs-5">{{ \App\Support\Money::brl($order->total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-admin.card>

            {{-- Customer --}}
            <x-admin.card title="Cliente e entrega" class="mt-3">
                <div class="info-grid">
                    <div>
                        <p class="info-grid__label">Nome</p>
                        <p class="info-grid__value mb-0">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <p class="info-grid__label">E-mail</p>
                        <p class="info-grid__value mb-0">
                            <a href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a>
                        </p>
                    </div>
                    <div>
                        <p class="info-grid__label">Telefone</p>
                        <p class="info-grid__value mb-0">
                            <a href="{{ $order->customer_whatsapp_url }}" target="_blank" rel="noopener">
                                <i class="bi bi-whatsapp me-1" aria-hidden="true"></i>{{ $order->formatted_phone }}
                            </a>
                        </p>
                    </div>

                    @if ($order->address)
                        <div style="grid-column: 1 / -1">
                            <p class="info-grid__label">Endereço de entrega</p>
                            <p class="info-grid__value mb-0">
                                {{ $order->address->line1 }}<br>
                                <span class="fw-normal text-muted">{{ $order->address->line2 }}</span>
                                @if ($order->address->reference)
                                    <br><span class="fw-normal text-muted">Referência: {{ $order->address->reference }}</span>
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

                @if ($order->notes)
                    <div class="alert alert-warning mt-3 mb-0" role="note">
                        <i class="bi bi-chat-left-text-fill" aria-hidden="true"></i>
                        <div>
                            <strong class="d-block">Observações do cliente</strong>
                            {{ $order->notes }}
                        </div>
                    </div>
                @endif
            </x-admin.card>

            {{-- Payments --}}
            <x-admin.card title="Transações" class="mt-3" flush>
                @if ($order->payments->isEmpty())
                    <div class="admin-card__body">
                        <p class="text-muted mb-0">Nenhuma transação registrada para este pedido.</p>
                    </div>
                @else
                    <div class="table-scroll">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>ID Mercado Pago</th>
                                    <th>Forma</th>
                                    <th>Valor</th>
                                    <th>Situação</th>
                                    <th class="text-end">Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->payments as $payment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.payments.show', $payment) }}" class="order-ref">
                                                {{ $payment->external_id }}
                                            </a>
                                        </td>
                                        <td>{{ $payment->method_label }}</td>
                                        <td class="text-nowrap">{{ \App\Support\Money::brl($payment->amount) }}</td>
                                        <td><x-status-badge :status="$payment->status" soft /></td>
                                        <td class="text-end text-muted text-nowrap">
                                            {{ $payment->created_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.card>
        </div>

        {{-- Side --}}
        <div class="col-xl-4">

            <x-admin.card title="Situação">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <x-status-badge :status="$order->status" />
                    <x-status-badge :status="$order->payment_status" />
                </div>

                @if ($order->paid_at)
                    <p class="text-muted mb-3" style="font-size:.875rem">
                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                        Pago em {{ $order->paid_at->format('d/m/Y \à\s H:i') }}
                    </p>
                @endif

                @if ($transitions === [])
                    <div class="alert alert-info mb-0" role="status">
                        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                        <div>Este pedido chegou ao fim do fluxo e não pode mais mudar de situação.</div>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                        @csrf
                        @method('PATCH')

                        <x-form.select
                            name="status"
                            label="Mudar para"
                            :value="$order->status->value"
                            :options="collect($transitions)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                            placeholder="Selecione…"
                            required
                        />

                        <x-form.textarea
                            name="internal_notes"
                            label="Observações internas"
                            :value="$order->internal_notes"
                            rows="3"
                            maxlength="2000"
                            hint="Visível apenas para a equipe."
                        />

                        <div class="d-grid">
                            <x-button variant="primary" icon="check2">Atualizar situação</x-button>
                        </div>
                    </form>
                @endif
            </x-admin.card>

            <x-admin.card title="Referências" class="mt-3">
                <div class="settings-row">
                    <span class="settings-row__label">Número</span>
                    <span class="settings-row__value order-ref">{{ $order->public_number }}</span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">ID Mercado Pago</span>
                    <span class="settings-row__value">{{ $order->gateway_payment_id ?: '—' }}</span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Preferência</span>
                    <span class="settings-row__value">{{ $order->gateway_preference_id ?: '—' }}</span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">IP de origem</span>
                    <span class="settings-row__value">{{ $order->ip_address ?: '—' }}</span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Última atualização</span>
                    <span class="settings-row__value">{{ $order->updated_at->format('d/m/Y H:i') }}</span>
                </div>
            </x-admin.card>

            <div class="d-grid gap-2 mt-3">
                <a
                    href="{{ $order->customer_whatsapp_url }}?text={{ urlencode('Olá, '.$order->customer_name.'! Sobre o seu pedido '.$order->public_number.'…') }}"
                    class="btn btn-outline-success d-inline-flex align-items-center justify-content-center gap-2"
                    target="_blank"
                    rel="noopener"
                >
                    <i class="bi bi-whatsapp" aria-hidden="true"></i>
                    Falar com o cliente
                </a>
            </div>
        </div>
    </div>

@endsection

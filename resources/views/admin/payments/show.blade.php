@extends('layouts.admin')

@section('title', 'Transação '.$payment->external_id)

@section('content')

    <x-admin.page-head
        :title="'Transação '.$payment->external_id"
        :subtitle="'Registrada em '.$payment->created_at->translatedFormat('d \d\e F \d\e Y, \à\s H:i')"
    >
        <x-slot:actions>
            @can('sync', $payment)
                <form method="POST" action="{{ route('admin.payments.sync', $payment) }}">
                    @csrf
                    <x-button variant="primary" icon="arrow-repeat">
                        Reconsultar no Mercado Pago
                    </x-button>
                </form>
            @endcan

            <x-button href="{{ route('admin.payments.index') }}" variant="outline-secondary" icon="arrow-left">
                Voltar
            </x-button>
        </x-slot:actions>
    </x-admin.page-head>

    <div class="row g-3">
        <div class="col-xl-5">
            <x-admin.card title="Resumo">
                <div class="settings-row">
                    <span class="settings-row__label">Situação</span>
                    <span class="settings-row__value"><x-status-badge :status="$payment->status" soft /></span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Situação no gateway</span>
                    <span class="settings-row__value">
                        {{ $payment->gateway_status ?: '—' }}
                        @if ($payment->gateway_status_detail)
                            <span class="d-block text-muted" style="font-size:.8125rem">{{ $payment->gateway_status_detail }}</span>
                        @endif
                    </span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Valor</span>
                    <span class="settings-row__value fw-semibold">{{ \App\Support\Money::brl($payment->amount) }}</span>
                </div>
                @if ($payment->net_amount)
                    <div class="settings-row">
                        <span class="settings-row__label">Valor líquido</span>
                        <span class="settings-row__value">{{ \App\Support\Money::brl($payment->net_amount) }}</span>
                    </div>
                @endif
                <div class="settings-row">
                    <span class="settings-row__label">Forma de pagamento</span>
                    <span class="settings-row__value">{{ $payment->method_label }}</span>
                </div>
                @if ($payment->installments)
                    <div class="settings-row">
                        <span class="settings-row__label">Parcelas</span>
                        <span class="settings-row__value">{{ $payment->installments }}x</span>
                    </div>
                @endif
                <div class="settings-row">
                    <span class="settings-row__label">Pagador</span>
                    <span class="settings-row__value">{{ $payment->payer_email ?: '—' }}</span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Aprovado em</span>
                    <span class="settings-row__value">
                        {{ $payment->approved_at?->format('d/m/Y H:i') ?: '—' }}
                    </span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Preferência</span>
                    <span class="settings-row__value">{{ $payment->preference_id ?: '—' }}</span>
                </div>
            </x-admin.card>

            @if ($payment->order)
                <x-admin.card title="Pedido vinculado" class="mt-3">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <a href="{{ route('admin.orders.show', $payment->order) }}" class="order-ref fs-6">
                            {{ $payment->order->public_number }}
                        </a>
                        <x-status-badge :status="$payment->order->status" soft />
                    </div>

                    <div class="settings-row">
                        <span class="settings-row__label">Cliente</span>
                        <span class="settings-row__value">{{ $payment->order->customer_name }}</span>
                    </div>
                    <div class="settings-row">
                        <span class="settings-row__label">Total do pedido</span>
                        <span class="settings-row__value fw-semibold">
                            {{ \App\Support\Money::brl($payment->order->total) }}
                        </span>
                    </div>

                    @if ((float) $payment->amount + 0.01 < (float) $payment->order->total)
                        <div class="alert alert-danger mt-3 mb-0" role="alert">
                            <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                            <div>
                                <strong class="d-block">Valor divergente</strong>
                                A transação é menor que o total do pedido. O pedido não foi marcado como pago.
                            </div>
                        </div>
                    @endif
                </x-admin.card>
            @endif
        </div>

        <div class="col-xl-7">
            <x-admin.card title="Resposta do Mercado Pago">
                <p class="text-muted" style="font-size:.875rem">
                    Conteúdo bruto retornado pela API, guardado para auditoria e contestações.
                </p>

                <pre class="payload-view">{{ json_encode($payment->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </x-admin.card>
        </div>
    </div>

@endsection

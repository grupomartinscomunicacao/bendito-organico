@extends('layouts.admin')

@section('title', 'Pagamentos')

@section('content')

    <x-admin.page-head
        title="Pagamentos"
        subtitle="Transações registradas pelo Mercado Pago."
    />

    <x-admin.card flush>
        <div class="admin-card__body border-bottom">
            <form method="GET" action="{{ route('admin.payments.index') }}" class="filter-bar">
                <div style="grid-column: span 2">
                    <label class="form-label" for="p-q">Buscar</label>
                    <input
                        type="search"
                        id="p-q"
                        name="q"
                        value="{{ $filters['q'] }}"
                        class="form-control"
                        placeholder="ID da transação, pedido, e-mail do pagador…"
                    >
                </div>

                <div>
                    <label class="form-label" for="p-status">Situação</label>
                    <select name="status" id="p-status" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">Limpar</a>
                    @endif
                </div>
            </form>
        </div>

        @if ($payments->isEmpty())
            <div class="admin-card__body">
                <x-empty-state icon="credit-card-2-front" title="Nenhuma transação ainda">
                    As transações aparecem aqui assim que o Mercado Pago confirmar um pagamento.
                </x-empty-state>
            </div>
        @else
            <div class="table-scroll">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Transação</th>
                            <th>Pedido</th>
                            <th class="d-none d-md-table-cell">Forma</th>
                            <th>Valor</th>
                            <th>Situação</th>
                            <th class="d-none d-lg-table-cell">Data</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="order-ref">
                                        {{ $payment->external_id }}
                                    </a>
                                </td>

                                <td>
                                    @if ($payment->order)
                                        <a href="{{ route('admin.orders.show', $payment->order) }}" class="order-ref">
                                            {{ $payment->order->public_number }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="d-none d-md-table-cell">{{ $payment->method_label }}</td>
                                <td class="fw-semibold text-nowrap">{{ \App\Support\Money::brl($payment->amount) }}</td>
                                <td><x-status-badge :status="$payment->status" soft /></td>

                                <td class="d-none d-lg-table-cell text-muted text-nowrap">
                                    {{ $payment->created_at->format('d/m/Y H:i') }}
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($payments->hasPages())
                <x-slot:footer>
                    {{ $payments->links() }}
                </x-slot:footer>
            @endif
        @endif
    </x-admin.card>

@endsection

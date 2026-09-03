@extends('layouts.admin')

@section('title', 'Pedidos')

@section('content')

    <x-admin.page-head title="Pedidos" subtitle="Acompanhe e atualize a situação de cada pedido." />

    <x-admin.card flush>
        <div class="admin-card__body border-bottom">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="filter-bar">
                <div style="grid-column: span 2">
                    <label class="form-label" for="f-q">Buscar</label>
                    <input
                        type="search"
                        id="f-q"
                        name="q"
                        value="{{ $filters['q'] }}"
                        class="form-control"
                        placeholder="Número, cliente, e-mail…"
                    >
                </div>

                <div>
                    <label class="form-label" for="f-status">Situação</label>
                    <select name="status" id="f-status" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="f-pagamento">Pagamento</label>
                    <select name="pagamento" id="f-pagamento" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($paymentOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['pagamento'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="f-de">De</label>
                    <input type="date" id="f-de" name="de" value="{{ $filters['de'] }}" class="form-control">
                </div>

                <div>
                    <label class="form-label" for="f-ate">Até</label>
                    <input type="date" id="f-ate" name="ate" value="{{ $filters['ate'] }}" class="form-control">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Limpar</a>
                    @endif
                </div>
            </form>
        </div>

        @if ($orders->isEmpty())
            <div class="admin-card__body">
                <x-empty-state icon="receipt" title="Nenhum pedido encontrado">
                    Ajuste os filtros ou aguarde a próxima compra.
                </x-empty-state>
            </div>
        @else
            <div class="table-scroll">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th class="d-none d-lg-table-cell">Produto</th>
                            <th>Total</th>
                            <th>Pagamento</th>
                            <th>Situação</th>
                            <th class="d-none d-md-table-cell">Data</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php $first = $order->items->first(); @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="order-ref">
                                        {{ $order->public_number }}
                                    </a>
                                </td>

                                <td>
                                    <span class="d-block fw-semibold text-truncate" style="max-width:13rem">{{ $order->customer_name }}</span>
                                    <span class="text-muted text-truncate d-block" style="max-width:13rem;font-size:.8125rem">
                                        {{ $order->customer_email }}
                                    </span>
                                </td>

                                <td class="d-none d-lg-table-cell">
                                    @if ($first)
                                        <span class="d-block text-truncate" style="max-width:14rem">{{ $first->product_name }}</span>
                                        <span class="text-muted" style="font-size:.8125rem">
                                            {{ $first->formatted_quantity }} {{ $first->product_unit->abbreviation() }}
                                            @if ($order->items->count() > 1)
                                                &middot; +{{ $order->items->count() - 1 }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="fw-semibold text-nowrap">{{ \App\Support\Money::brl($order->total) }}</td>
                                <td><x-status-badge :status="$order->payment_status" soft /></td>
                                <td><x-status-badge :status="$order->status" soft /></td>

                                <td class="d-none d-md-table-cell text-muted text-nowrap">
                                    {{ $order->created_at->format('d/m/Y') }}
                                    <span class="d-block" style="font-size:.8125rem">{{ $order->created_at->format('H:i') }}</span>
                                </td>

                                <td class="text-end text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-primary" title="Detalhes">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="{{ route('admin.orders.print', $order) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener" title="Imprimir cartão">
                                            <i class="bi bi-printer" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <x-slot:footer>
                    {{ $orders->links() }}
                </x-slot:footer>
            @endif
        @endif
    </x-admin.card>

@endsection

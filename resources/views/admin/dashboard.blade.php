@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

    <x-admin.page-head
        title="Olá, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }} 👋"
        subtitle="Um resumo de como a loja está indo hoje."
    >
        <x-slot:actions>
            <x-button href="{{ route('admin.products.create') }}" variant="primary" icon="plus-lg">
                Novo produto
            </x-button>
            <x-button href="{{ route('admin.orders.index') }}" variant="outline-primary" icon="receipt">
                Ver pedidos
            </x-button>
        </x-slot:actions>
    </x-admin.page-head>

    {{-- Revenue --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <x-admin.stat
                label="Faturamento total"
                :value="\App\Support\Money::brl($revenue['total'])"
                icon="cash-stack"
                variant="secondary"
                hint="Somente pedidos pagos"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin.stat
                label="Faturamento do mês"
                :value="\App\Support\Money::brl($revenue['month'])"
                icon="calendar-check"
                variant="highlight"
                :hint="now()->translatedFormat('F \d\e Y')"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin.stat
                label="Vendas de hoje"
                :value="\App\Support\Money::brl($revenue['today'])"
                icon="sun"
                variant="accent"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin.stat
                label="Ticket médio"
                :value="\App\Support\Money::brl($revenue['average_ticket'])"
                icon="graph-up-arrow"
                variant="info"
            />
        </div>
    </div>

    {{-- Orders --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4 col-xl-2">
            <x-admin.stat
                label="Total de pedidos"
                :value="$counts['total']"
                icon="receipt"
                variant="primary"
                :href="route('admin.orders.index')"
            />
        </div>
        <div class="col-6 col-lg-4 col-xl-2">
            <x-admin.stat
                label="Aguardando pagamento"
                :value="$counts['awaiting_payment']"
                icon="hourglass-split"
                variant="accent"
                :href="route('admin.orders.index', ['pagamento' => 'pending'])"
            />
        </div>
        <div class="col-6 col-lg-4 col-xl-2">
            <x-admin.stat
                label="Pagos"
                :value="$counts['paid']"
                icon="patch-check"
                variant="secondary"
                :href="route('admin.orders.index', ['pagamento' => 'approved'])"
            />
        </div>
        <div class="col-6 col-lg-4 col-xl-2">
            <x-admin.stat
                label="Em preparação"
                :value="$counts['preparing']"
                icon="basket"
                variant="info"
                :href="route('admin.orders.index', ['status' => 'preparing'])"
            />
        </div>
        <div class="col-6 col-lg-4 col-xl-2">
            <x-admin.stat
                label="Entregues"
                :value="$counts['delivered']"
                icon="house-check"
                variant="secondary"
                :href="route('admin.orders.index', ['status' => 'delivered'])"
            />
        </div>
        <div class="col-6 col-lg-4 col-xl-2">
            <x-admin.stat
                label="Produtos ativos"
                :value="$catalog['active']"
                icon="box-seam"
                variant="highlight"
                :hint="$catalog['total'].' cadastrados'"
                :href="route('admin.products.index')"
            />
        </div>
    </div>

    <div class="row g-3">

        {{-- Recent orders --}}
        <div class="col-xl-8">
            <x-admin.card title="Últimos pedidos" flush>
                <x-slot:actions>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
                </x-slot:actions>

                @if ($recentOrders->isEmpty())
                    <div class="admin-card__body">
                        <x-empty-state icon="receipt" title="Nenhum pedido ainda">
                            Quando um cliente finalizar uma compra, ela aparece aqui.
                        </x-empty-state>
                    </div>
                @else
                    <div class="table-scroll">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th class="d-none d-md-table-cell">Itens</th>
                                    <th>Total</th>
                                    <th>Pagamento</th>
                                    <th class="d-none d-lg-table-cell">Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $order) }}" class="order-ref">
                                                {{ $order->public_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="d-block text-truncate" style="max-width: 14rem">{{ $order->customer_name }}</span>
                                        </td>
                                        <td class="d-none d-md-table-cell text-muted">
                                            {{ $order->items->count() }}
                                        </td>
                                        <td class="fw-semibold text-nowrap">{{ \App\Support\Money::brl($order->total) }}</td>
                                        <td><x-status-badge :status="$order->payment_status" soft /></td>
                                        <td class="d-none d-lg-table-cell text-muted text-nowrap">
                                            {{ $order->created_at->format('d/m/Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.card>
        </div>

        {{-- Low stock --}}
        <div class="col-xl-4">
            <x-admin.card title="Estoque baixo">
                @if ($lowStock->isEmpty())
                    <div class="text-center py-3">
                        <i class="bi bi-check-circle d-block mb-2" style="font-size:2rem;color:var(--color-secondary)" aria-hidden="true"></i>
                        <p class="text-muted mb-0" style="font-size:.9375rem">Tudo em ordem — nenhum produto acabando.</p>
                    </div>
                @else
                    <ul class="list-unstyled d-grid gap-3 mb-0">
                        @foreach ($lowStock as $product)
                            <li class="d-flex align-items-center gap-3">
                                <img src="{{ $product->image_url }}" alt="" class="table-thumb" width="44" height="44" loading="lazy">

                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="d-block text-truncate fw-semibold link-quiet">
                                        {{ $product->name }}
                                    </a>
                                    <span class="text-muted" style="font-size:.8125rem">
                                        {{ \App\Support\Money::quantity($product->stock) }} {{ $product->unit->abbreviationFor((float) $product->stock) }} restantes
                                    </span>
                                </div>

                                <span class="badge-soft badge-soft--{{ (float) $product->stock <= 0 ? 'danger' : 'warning' }}">
                                    {{ (float) $product->stock <= 0 ? 'Esgotado' : 'Baixo' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>

            @if ($catalog['out_of_stock'] > 0)
                <div class="alert alert-warning mt-3" role="status">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    <div>
                        <strong>{{ $catalog['out_of_stock'] }}</strong>
                        {{ \Illuminate\Support\Str::plural('produto', $catalog['out_of_stock']) }}
                        {{ $catalog['out_of_stock'] === 1 ? 'está esgotado' : 'estão esgotados' }} e não
                        {{ $catalog['out_of_stock'] === 1 ? 'aparece' : 'aparecem' }} na loja.
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@extends('layouts.app')

@section('title', 'Meu pedido')
@section('description', 'Consulte o andamento dos seus pedidos usando o telefone informado na compra.')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')

    <div class="container section-narrow">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-7 col-xl-6">

                @if ($orders === null)

                    {{-- ── Step 1: who are you? ──────────────────────────── --}}
                    <section class="checkout-panel lookup-panel">
                        <span class="lookup-panel__icon">
                            <i class="bi bi-bag-check" aria-hidden="true"></i>
                        </span>

                        <h1 class="lookup-panel__title">Meu pedido</h1>
                        <p class="lookup-panel__lead">
                            Digite o telefone que você informou na compra para acompanhar seus pedidos.
                        </p>

                        <form method="POST" action="{{ route('orders.lookup.search') }}" data-loading>
                            @csrf

                            <x-form.input
                                name="telefone"
                                label="Telefone"
                                type="tel"
                                icon="telephone"
                                placeholder="(00) 00000-0000"
                                inputmode="numeric"
                                autocomplete="tel"
                                data-mask="phone"
                                enterkeyhint="search"
                                autofocus
                                required
                            />

                            <button type="submit" class="btn btn-primary btn-lg w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                <span class="btn-spinner spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <span class="btn-label">Continuar</span>
                                <i class="bi bi-arrow-right btn-label" aria-hidden="true"></i>
                            </button>
                        </form>

                        <p class="lookup-panel__foot">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                            Mostramos apenas os pedidos feitos com este número.
                        </p>
                    </section>

                @elseif ($orders->isEmpty())

                    {{-- ── Nothing on file for that number ───────────────── --}}
                    <x-empty-state icon="search" title="Nenhum pedido encontrado">
                        Não encontramos pedidos associados ao telefone {{ $phone }}.

                        <x-slot:action>
                            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                                <x-button href="{{ route('orders.lookup', ['novo' => 1]) }}" variant="primary" icon="telephone">
                                    Tentar outro telefone
                                </x-button>
                                <x-button href="{{ route('products.index') }}" variant="outline-primary" icon="basket2">
                                    Fazer um pedido
                                </x-button>
                            </div>
                        </x-slot:action>
                    </x-empty-state>

                @else

                    {{-- ── Step 2: pick an order ─────────────────────────── --}}
                    <header class="lookup-head">
                        <div>
                            <h1 class="lookup-head__title">Seus pedidos</h1>
                            <p class="lookup-head__meta mb-0">{{ $phone }}</p>
                        </div>

                        <a href="{{ route('orders.lookup', ['novo' => 1]) }}" class="lookup-head__switch">
                            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                            Trocar
                        </a>
                    </header>

                    <div class="d-grid gap-3">
                        @foreach ($orders as $order)
                            <a href="{{ route('orders.show', $order) }}" class="order-card">
                                <div class="order-card__body">
                                    <p class="order-card__date">
                                        Pedido realizado em {{ $order->created_at->format('d/m/Y') }}
                                    </p>

                                    <p class="order-card__items">
                                        {{ $order->items->pluck('product_name')->join(', ') }}
                                    </p>

                                    <div class="order-card__foot">
                                        <x-status-badge :status="$order->status" soft />
                                        <strong class="order-card__total">{{ \App\Support\Money::brl($order->total) }}</strong>
                                    </div>
                                </div>

                                <i class="bi bi-chevron-right order-card__chevron" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>

                    <div class="text-center mt-4">
                        <x-button href="{{ route('products.index') }}" variant="outline-primary" icon="basket2">
                            Fazer um novo pedido
                        </x-button>
                    </div>

                @endif

            </div>
        </div>
    </div>

@endsection

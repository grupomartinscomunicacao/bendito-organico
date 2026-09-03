@extends('layouts.app')

@section('title', 'Finalizar pedido')
@section('description', 'Informe seus dados e endereço para concluir seu pedido no Bendito Orgânico.')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')

    <div class="container py-4 py-lg-5">

        <div class="checkout-steps">
            <span class="checkout-steps__item checkout-steps__item--done">
                <span class="checkout-steps__dot"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                Produto
            </span>
            <span class="checkout-steps__sep"></span>
            <span class="checkout-steps__item checkout-steps__item--current">
                <span class="checkout-steps__dot">2</span>
                Seus dados
            </span>
            <span class="checkout-steps__sep"></span>
            <span class="checkout-steps__item">
                <span class="checkout-steps__dot">3</span>
                Pagamento
            </span>
        </div>

        <div class="row g-4">

            {{-- Form --}}
            <div class="col-lg-7 col-xl-8">
                <form method="POST" action="{{ route('orders.store') }}" id="checkout-form" novalidate>
                    @csrf

                    {{-- Dados pessoais --}}
                    <section class="checkout-panel">
                        <header class="checkout-panel__head">
                            <span class="step-marker">1</span>
                            <div>
                                <h2>Seus dados</h2>
                                <p>Usamos apenas para confirmar o pedido e combinar a entrega.</p>
                            </div>
                        </header>

                        <div class="row">
                            <div class="col-12">
                                <x-form.input
                                    name="customer_name"
                                    label="Nome completo"
                                    placeholder="Maria Aparecida Silva"
                                    autocomplete="name"
                                    icon="person"
                                    required
                                />
                            </div>
                            <div class="col-md-7">
                                <x-form.input
                                    name="customer_email"
                                    type="email"
                                    label="E-mail"
                                    placeholder="voce@email.com"
                                    autocomplete="email"
                                    icon="envelope"
                                    hint="Enviamos a confirmação e o link do pedido para este e-mail."
                                    required
                                />
                            </div>
                            <div class="col-md-5">
                                <x-form.input
                                    name="customer_phone"
                                    type="tel"
                                    label="Telefone / WhatsApp"
                                    placeholder="(11) 98888-7777"
                                    autocomplete="tel"
                                    icon="whatsapp"
                                    data-mask="phone"
                                    required
                                />
                            </div>
                        </div>
                    </section>

                    {{-- Endereço --}}
                    <section class="checkout-panel">
                        <header class="checkout-panel__head">
                            <span class="step-marker">2</span>
                            <div>
                                <h2>Endereço de entrega</h2>
                                <p>Preencha o CEP e completamos o resto para você.</p>
                            </div>
                        </header>

                        <div class="row">
                            <div class="col-md-4">
                                <x-form.input
                                    name="zip_code"
                                    label="CEP"
                                    placeholder="01001-000"
                                    inputmode="numeric"
                                    autocomplete="postal-code"
                                    data-mask="zip"
                                    data-address-zip
                                    required
                                />
                                <small class="form-hint" data-address-status></small>
                            </div>
                            <div class="col-md-5">
                                <x-form.input
                                    name="city"
                                    label="Cidade"
                                    autocomplete="address-level2"
                                    required
                                />
                            </div>
                            <div class="col-md-3">
                                <x-form.select
                                    name="state"
                                    label="Estado"
                                    placeholder="UF"
                                    autocomplete="address-level1"
                                    :options="collect(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'])->mapWithKeys(fn (string $uf) => [$uf => $uf])->all()"
                                    required
                                />
                            </div>
                            <div class="col-md-6">
                                <x-form.input
                                    name="district"
                                    label="Bairro"
                                    autocomplete="address-level3"
                                    required
                                />
                            </div>
                            <div class="col-md-6">
                                <x-form.input
                                    name="street"
                                    label="Rua"
                                    autocomplete="address-line1"
                                    required
                                />
                            </div>
                            <div class="col-md-3">
                                <x-form.input
                                    name="number"
                                    label="Número"
                                    placeholder="120"
                                    autocomplete="address-line2"
                                    required
                                />
                            </div>
                            <div class="col-md-4">
                                <x-form.input name="complement" label="Complemento" placeholder="Apto 21" />
                            </div>
                            <div class="col-md-5">
                                <x-form.input name="reference" label="Ponto de referência" placeholder="Portão verde, ao lado da padaria" />
                            </div>
                        </div>
                    </section>

                    {{-- Observações --}}
                    <section class="checkout-panel">
                        <header class="checkout-panel__head">
                            <span class="step-marker">3</span>
                            <div>
                                <h2>Observações do pedido</h2>
                                <p>Algum detalhe que devemos saber? (opcional)</p>
                            </div>
                        </header>

                        <x-form.textarea
                            name="notes"
                            label="Observações"
                            rows="3"
                            placeholder="Ex.: entregar depois das 14h, tocar a campainha do portão azul…"
                            maxlength="1000"
                        />

                        <x-form.checkbox
                            name="terms"
                            label="Confirmo que meus dados e o endereço de entrega estão corretos."
                            class="mb-0"
                        />
                    </section>

                    <div class="d-grid d-lg-none mt-4">
                        <button type="submit" class="btn btn-accent btn-lg d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            Ir para o pagamento
                        </button>
                    </div>
                </form>
            </div>

            {{-- Summary --}}
            <div class="col-lg-5 col-xl-4">
                <x-order-summary
                    :items="[[
                        'name' => $product->name,
                        'image' => $product->image_url,
                        'quantity' => \App\Support\Money::quantity($quantity),
                        'unit' => $product->unit->abbreviation(),
                        'unit_price' => $product->price,
                        'subtotal' => $subtotal,
                    ]]"
                    :subtotal="$subtotal"
                    :delivery-fee="$deliveryFee"
                    :total="$total"
                    sticky
                >
                    {{-- The HTML5 "form" attribute lets this button submit the
                         checkout form it sits outside of — no JS proxy needed. --}}
                    <div class="d-none d-lg-grid mt-4">
                        <button
                            type="submit"
                            form="checkout-form"
                            class="btn btn-accent btn-lg d-inline-flex align-items-center justify-content-center gap-2"
                        >
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            Ir para o pagamento
                        </button>
                    </div>

                    <p class="payment-hint justify-content-center mt-3 mb-0">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                        Ambiente seguro — Mercado Pago
                    </p>

                    <div class="mt-3 pt-3 border-top">
                        <p class="text-muted mb-2" style="font-size:.8125rem">
                            <i class="bi bi-truck me-1" aria-hidden="true"></i>
                            {{ config('bendito.checkout.delivery_notice') }}
                        </p>
                        <a href="{{ route('products.show', $product) }}" class="link-quiet" style="font-size:.8125rem">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                            Alterar quantidade
                        </a>
                    </div>
                </x-order-summary>
            </div>
        </div>
    </div>

@endsection

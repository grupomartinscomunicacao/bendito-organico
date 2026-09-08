@extends('layouts.app')

@section('title', $product->meta_title ?: $product->name.' orgânica')
@section('description', $product->meta_description ?: $product->short_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 160))
@section('og_type', 'product')
@section('og_image', $product->image_url)

@push('head')
    @php
        // Built here rather than inline in @json: Blade's directive parser
        // cannot handle a multi-line nested array as an argument.
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->short_description ?: strip_tags((string) $product->description),
            'image' => $product->image_url,
            'sku' => $product->slug,
            'brand' => ['@type' => 'Brand', 'name' => config('bendito.name')],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('products.show', $product),
                'priceCurrency' => 'BRL',
                'price' => (string) $product->price,
                'availability' => $product->isAvailable()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ];
    @endphp

    {{-- Product schema so search engines can show price and availability.
         JSON_HEX_TAG keeps a stray "</script>" in any field from breaking out. --}}
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}
    </script>
@endpush

@section('content')

    <div class="container py-4 py-lg-5">

        <nav aria-label="Você está aqui">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Início</a></li>
                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Produtos</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="row g-4 g-lg-5">

            {{-- Photo --}}
            <div class="col-lg-6">
                <figure class="product-gallery mb-0">
                    <img
                        src="{{ $product->image_url }}"
                        alt="{{ $product->name }} — {{ config('bendito.name') }}"
                        width="900"
                        height="900"
                        fetchpriority="high"
                    >

                    <div class="product-gallery__flags">
                        @if ($product->has_discount)
                            <span class="badge text-bg-accent fs-6">-{{ $product->discount_percentage }}%</span>
                        @endif
                        @if ($product->is_featured)
                            <span class="badge text-bg-highlight fs-6">Destaque da semana</span>
                        @endif
                    </div>
                </figure>
            </div>

            {{-- Detail + buy box --}}
            <div class="col-lg-6 product-detail">
                <span class="badge-soft badge-soft--{{ $product->availability_variant }} mb-3">
                    <i class="bi bi-circle-fill" style="font-size:.5rem" aria-hidden="true"></i>
                    {{ $product->availability_label }}
                </span>

                <h1 class="product-detail__title">{{ $product->name }}</h1>

                @if ($product->short_description)
                    <p class="product-detail__lead">{{ $product->short_description }}</p>
                @endif

                <div class="product-detail__price my-4">
                    <x-price
                        :value="$product->price"
                        :unit="$product->unit"
                        :compare-at="$product->has_discount ? $product->compare_at_price : null"
                    />
                </div>

                <dl class="spec-list mb-4">
                    <div>
                        <dt>Unidade de venda</dt>
                        <dd>{{ $product->unit->label() }}</dd>
                    </div>
                    <div>
                        <dt>Disponibilidade</dt>
                        <dd>{{ $product->availability_label }}</dd>
                    </div>
                    @if ($product->track_stock && $product->isAvailable())
                        <div>
                            <dt>Em estoque</dt>
                            <dd>{{ \App\Support\Money::quantity($product->stock) }} {{ $product->unit->abbreviationFor((float) $product->stock) }}</dd>
                        </div>
                    @endif
                </dl>

                {{-- Buy box --}}
                @php
                    $minimumOrder = (float) config('bendito.checkout.minimum_order', 0);
                    $minimumQuantity = $product->minimumOrderQuantity();
                    $canMeetMinimum = $product->canMeetMinimumOrder();
                @endphp

                <div class="buy-box mb-4">
                    @if ($product->isAvailable() && $canMeetMinimum)
                        <form method="POST" action="{{ route('checkout.start') }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                            <label class="form-label" for="quantidade">Quantidade</label>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <x-quantity-input :product="$product" id="quantidade" />
                                {{-- Acompanha o seletor: com o mínimo em 12 o rótulo abre
                                     em "maços", e volta para "maço" se a quantidade cair
                                     para 1. Os dois valores vêm do servidor porque o
                                     plural em português não sai de uma regra genérica. --}}
                                <span
                                    class="text-muted"
                                    style="font-size:.9375rem"
                                    data-quantity-unit
                                    data-unit-one="{{ $product->unit->abbreviation() }}"
                                    data-unit-many="{{ $product->unit->abbreviationFor(2) }}"
                                >{{ $product->unit->abbreviationFor($minimumQuantity) }}</span>
                            </div>

                            @if ($minimumOrder > 0)
                                {{-- O seletor já abre no mínimo comprável, então isto explica
                                     por que o número inicial não é 1 — sem a frase, a página
                                     pareceria ter escolhido a quantidade por conta própria. --}}
                                <p class="buy-box__minimum mt-3 mb-0">
                                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                                    <span>
                                        Pedido mínimo de {{ \App\Support\Money::brl($minimumOrder) }} —
                                        a partir de {{ \App\Support\Money::quantity($minimumQuantity) }}
                                        {{ $product->unit->abbreviationFor($minimumQuantity) }} deste item.
                                    </span>
                                </p>
                            @endif

                            <div class="buy-box__total">
                                <span>Total do pedido</span>
                                <strong data-quantity-total>{{ \App\Support\Money::brl($product->subtotalFor($minimumQuantity)) }}</strong>
                            </div>

                            <div class="d-grid mt-3">
                                <button type="submit" class="btn btn-accent btn-lg d-inline-flex align-items-center justify-content-center gap-2" data-quantity-submit>
                                    <i class="bi bi-bag-check" aria-hidden="true"></i>
                                    Fazer pedido
                                </button>
                            </div>

                            <p class="payment-hint justify-content-center mt-3 mb-0">
                                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                                Pagamento seguro via Mercado Pago — Pix ou cartão de crédito
                            </p>
                        </form>
                    @elseif ($product->isAvailable() && ! $canMeetMinimum)
                        {{-- Disponível, mas o estoque inteiro não chega ao pedido mínimo:
                             oferecer o botão só levaria a um erro garantido. --}}
                        <div class="text-center py-2">
                            <i class="bi bi-basket2 d-block mb-2" style="font-size:2rem;color:var(--color-highlight)" aria-hidden="true"></i>
                            <strong class="d-block mb-1">Estoque insuficiente para o pedido mínimo</strong>
                            <p class="text-muted mb-3" style="font-size:.9375rem">
                                O pedido mínimo é de {{ \App\Support\Money::brl($minimumOrder) }} e não temos
                                {{ $product->name }} suficiente para alcançá-lo agora. Veja o que mais saiu da horta hoje.
                            </p>
                            <x-button href="{{ route('products.index') }}" variant="primary" icon="arrow-left">
                                Ver outros produtos
                            </x-button>
                        </div>
                    @else
                        <div class="text-center py-2">
                            <i class="bi bi-basket2 d-block mb-2" style="font-size:2rem;color:var(--color-highlight)" aria-hidden="true"></i>
                            <strong class="d-block mb-1">{{ $product->availability_label }}</strong>
                            <p class="text-muted mb-3" style="font-size:.9375rem">
                                Este item está fora do catálogo no momento. Dê uma olhada no que colhemos hoje.
                            </p>
                            <x-button href="{{ route('products.index') }}" variant="primary" icon="arrow-left">
                                Ver outros produtos
                            </x-button>
                        </div>
                    @endif
                </div>

                @if ($product->description)
                    <div class="product-detail__description">
                        <h2 class="h5 mb-3">Sobre este produto</h2>
                        @foreach (preg_split('/\R{2,}/', trim($product->description)) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Related --}}
        @if ($related->isNotEmpty())
            <section class="mt-5 pt-4 border-top">
                <div class="section-head">
                    <div>
                        <span class="eyebrow mb-2">Combina bem</span>
                        <h2 class="h3 mb-0">Leve também</h2>
                    </div>
                </div>

                <div class="row g-4">
                    @foreach ($related as $item)
                        <div class="col-6 col-lg-3">
                            <x-product-card :product="$item" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

    </div>

@endsection

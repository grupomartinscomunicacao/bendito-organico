@extends('layouts.app')

@section('title', 'Orgânicos frescos, direto para você')
@section('description', 'Hortaliças, verduras e legumes orgânicos colhidos no dia e entregues frescos na sua casa. Sem agrotóxicos, direto do produtor.')

@section('content')

    {{-- ── Hero ───────────────────────────────────────────────────────────── --}}
    <section class="hero">
        {{-- Foto de capa. É o maior elemento da primeira dobra (o LCP da
             página), por isso carrega com prioridade em vez de lazy. --}}
        <picture class="hero__media">
            <source
                type="image/webp"
                srcset="{{ asset(config('bendito.hero.webp_768')) }} 768w,
                        {{ asset(config('bendito.hero.webp_1280')) }} 1280w,
                        {{ asset(config('bendito.hero.webp')) }} 1920w"
                sizes="100vw"
            >
            <img
                src="{{ asset(config('bendito.hero.jpg')) }}"
                alt=""
                width="1920"
                height="1080"
                fetchpriority="high"
                decoding="async"
            >
        </picture>

        <div class="container">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-7 hero__content">
                    <span class="eyebrow mb-3">
                        <i class="bi bi-flower1" aria-hidden="true"></i>
                        100% orgânico &middot; sem agrotóxicos
                    </span>

                    <h1 class="display-brand text-balance mb-3">
                        Orgânicos frescos,<br>direto para você.
                    </h1>

                    <p class="mb-4">
                        Plantamos, colhemos e entregamos no mesmo dia. Nossas hortaliças saem da
                        terra e vão direto para a sua cozinha — sem intermediários, sem agrotóxicos
                        e sem aquela semana parada em prateleira de mercado.
                    </p>

                    <div class="d-grid gap-2 d-sm-flex flex-sm-wrap gap-sm-3">
                        <x-button href="{{ route('products.index') }}" variant="accent" size="lg" icon="basket2">
                            Ver produtos
                        </x-button>
                        <x-button href="{{ route('about') }}" variant="outline-light" size="lg" icon="info-circle">
                            Conheça a horta
                        </x-button>
                    </div>
                </div>

                <div class="col-lg-5 text-center hero__content d-none d-lg-block">
                    <x-logo variant="light" size="xl" class="brand-logo hero__logo mx-auto" />
                </div>
            </div>
        </div>
    </section>

    {{-- ── Trust strip ────────────────────────────────────────────────────── --}}
    <section class="trust-strip">
        <div class="container">
            <div class="row g-4">
                @foreach ([
                    ['sun', 'Colhido no dia', 'A colheita acontece na manhã da sua entrega.'],
                    ['shield-check', 'Sem agrotóxicos', 'Manejo orgânico do plantio à embalagem.'],
                    ['truck', 'Entrega rápida', 'Combinamos o horário com você pelo WhatsApp.'],
                    ['credit-card-2-front', 'Pagamento seguro', 'Pix ou cartão via Mercado Pago.'],
                ] as [$icon, $title, $text])
                    <div class="col-6 col-lg-3">
                        <div class="trust-item">
                            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
                            <div>
                                <strong class="d-block" style="color: var(--color-primary)">{{ $title }}</strong>
                                <span class="text-muted" style="font-size: .875rem">{{ $text }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Showcase ───────────────────────────────────────────────────────── --}}
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow mb-2">Da nossa horta</span>
                    <h2>O que está fresco hoje</h2>
                    <p>Escolha, informe a quantidade e finalize em menos de dois minutos. Sem cadastro.</p>
                </div>

                <x-button
                    href="{{ route('products.index') }}"
                    variant="outline-primary"
                    icon-after="arrow-right"
                    class="w-100 w-sm-auto"
                >
                    Ver catálogo completo
                </x-button>
            </div>

            @if ($products->isEmpty())
                <x-empty-state icon="basket" title="Estamos preparando a próxima colheita">
                    Assim que os produtos forem cadastrados, eles aparecem aqui.
                </x-empty-state>
            @else
                <div class="row g-3 g-md-4">
                    @foreach ($products as $product)
                        <div class="col-6 col-lg-4 col-xl-3">
                            <x-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ── How it works ───────────────────────────────────────────────────── --}}
    <section class="section section--muted">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow mb-2">Simples assim</span>
                    <h2>Como funciona</h2>
                    <p>Quatro passos entre escolher a sua alface e recebê-la em casa.</p>
                </div>
            </div>

            <div class="row g-4">
                @foreach ([
                    ['Escolha o produto', 'Navegue pelo catálogo e abra o item que você quer.'],
                    ['Informe a quantidade', 'O total é calculado na hora, sem surpresas.'],
                    ['Preencha seus dados', 'Nome, contato e endereço de entrega. Sem criar conta.'],
                    ['Pague com segurança', 'Pix ou cartão pelo Mercado Pago.'],
                ] as $index => [$title, $text])
                    <div class="col-sm-6 col-lg-3">
                        <div class="d-flex gap-3">
                            <span class="step-marker">{{ $index + 1 }}</span>
                            <div>
                                <strong class="d-block mb-1" style="color: var(--color-primary)">{{ $title }}</strong>
                                <span class="text-muted" style="font-size: .9375rem">{{ $text }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── CTA ────────────────────────────────────────────────────────────── --}}
    <section class="pb-5">
        <div class="container">
            <div class="cta-band">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <h2 class="mb-2">Sua próxima salada começa aqui</h2>
                        <p>Peça hoje e receba na próxima janela de entrega. Falamos com você pelo WhatsApp para combinar o horário.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <x-button
                            href="{{ route('products.index') }}"
                            variant="light"
                            size="lg"
                            icon="basket2"
                            class="w-100 w-lg-auto"
                        >
                            Fazer meu pedido
                        </x-button>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

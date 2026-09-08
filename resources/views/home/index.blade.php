@extends('layouts.app')

@section('title', 'Orgânicos frescos, direto para você')
@section('description', 'Hortaliças, verduras e legumes orgânicos colhidos no dia e entregues frescos na sua casa. Sem agrotóxicos, direto do produtor.')

@section('content')

    {{-- ── Hero ───────────────────────────────────────────────────────────── --}}
    <section class="hero">
        {{-- Foto de capa. É o maior elemento da primeira dobra (o LCP da
             página), por isso carrega com prioridade em vez de lazy. --}}
        <x-hero-media priority />

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
                        terra e vão direto para a sua cozinha.
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
                    ['credit-card-2-front', 'Pagamento seguro', 'Pix ou cartão de crédito via Mercado Pago.'],
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
            <div class="section-head section-head--center">
                <div>
                    <span class="eyebrow mb-2">Simples assim</span>
                    <h2>Como funciona</h2>
                    <p>Quatro passos entre escolher a sua alface e recebê-la em casa.</p>
                </div>
            </div>

            {{-- <ol> e não uma grade de divs: a ordem é a informação. Quem usa
                 leitor de tela ouve "item 2 de 4" sem depender do número
                 desenhado, que é puramente visual e fica aria-hidden. --}}
            <ol class="step-grid">
                @foreach ([
                    ['basket', 'Escolha o produto', 'Navegue pelo catálogo e abra o item que você quer.'],
                    ['stepper', 'Informe a quantidade', 'O total é calculado na hora, sem surpresas.'],
                    ['form', 'Preencha seus dados', 'Nome, contato e endereço de entrega. Sem criar conta.'],
                    ['shield', 'Pague com segurança', 'Pix ou cartão de crédito pelo Mercado Pago.'],
                ] as $index => [$icon, $title, $text])
                    <li class="step-card">
                        <span class="step-card__icon">
                            <x-step-icon :name="$icon" />
                        </span>

                        <span class="step-card__index" aria-hidden="true">{{ $index + 1 }}</span>

                        <h3 class="step-card__title">{{ $title }}</h3>
                        <p class="step-card__text">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ── CTA ────────────────────────────────────────────────────────────── --}}
    <section class="pb-5">
        <div class="container">
            <div class="cta-band">
                {{-- Camada decorativa: dois halos e a silhueta de uma folha.
                     Fica num elemento próprio, e não no ::after do bloco, para
                     não competir com o gradiente de fundo. --}}
                <span class="cta-band__decor" aria-hidden="true">
                    <svg viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="3">
                        <path d="M168 32c0 62-38 100-100 100 0-62 38-100 100-100Z"/>
                        <path d="M168 32 52 148"/>
                    </svg>
                </span>

                <div class="row align-items-center g-4 g-lg-5">
                    <div class="col-lg-7">
                        <span class="eyebrow eyebrow--on-dark mb-2">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            Colheita da semana
                        </span>

                        <h2 class="text-balance mb-2">Sua próxima salada começa aqui</h2>
                        <p class="mb-0">Peça hoje e receba na próxima janela de entrega. Falamos com você pelo WhatsApp para combinar o horário.</p>

                        {{-- As três objeções que aparecem antes de alguém comprar
                             pela primeira vez: como pago, preciso me cadastrar,
                             e quando chega. Respondidas aqui, ao lado do botão. --}}
                        <ul class="cta-band__points">
                            <li><i class="bi bi-check-lg" aria-hidden="true"></i> Pix ou cartão de crédito</li>
                            <li><i class="bi bi-check-lg" aria-hidden="true"></i> Sem cadastro</li>
                            <li><i class="bi bi-check-lg" aria-hidden="true"></i> Terça a sábado</li>
                        </ul>
                    </div>

                    <div class="col-lg-5">
                        <div class="cta-band__action">
                            <x-button
                                href="{{ route('products.index') }}"
                                variant="accent"
                                size="lg"
                                icon="basket2"
                                class="w-100"
                            >
                                Fazer meu pedido
                            </x-button>

                            <p class="cta-band__note mb-0">
                                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                                Pagamento processado pelo Mercado Pago
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@extends('layouts.app')

@section('title', 'Sobre nós')
@section('description', 'Conheça a história do Bendito Orgânico: uma horta familiar que entrega verduras, legumes e hortaliças orgânicas direto do produtor para a sua casa.')

@section('content')

    <section class="page-hero">
        {{-- A mesma foto da home, aqui só como textura de fundo: a altura da
             faixa não muda, e o véu do CSS mantém o contraste do texto. --}}
        <x-hero-media />

        <div class="container">
            <nav aria-label="Você está aqui">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Início</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Sobre nós</li>
                </ol>
            </nav>

            <h1 class="display-brand mb-2">Da nossa terra para a sua mesa</h1>
            <p style="max-width: 52ch">Uma horta familiar que acredita em comida de verdade, feita com tempo e cuidado.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <span class="eyebrow mb-3">Nossa história</span>
                    <h2 class="mb-3">Plantar bem dá trabalho — e vale a pena</h2>

                    <p class="text-muted">
                        O Bendito Orgânico nasceu de um canteiro pequeno e de uma convicção
                        simples: quem planta com respeito à terra colhe alimento melhor. Sem
                        atalho químico, sem pressa artificial, sem aquela verdura que parece
                        bonita na prateleira e não tem gosto de nada.
                    </p>

                    <p class="text-muted">
                        Hoje cuidamos de canteiros que abastecem famílias da região inteira. O
                        manejo continua sendo o mesmo: adubação orgânica, rotação de culturas,
                        controle natural de pragas e colheita manual. Cada maço que sai daqui
                        passou pela mão de alguém que sabe o nome da planta.
                    </p>

                    <p class="text-muted mb-4">
                        Vender direto para você é o que fecha o ciclo. Sem atravessador, o
                        alimento chega em 24 horas e o produtor recebe o que é justo.
                    </p>

                    <x-button href="{{ route('products.index') }}" variant="primary" size="lg" icon="basket2">
                        Ver o que colhemos hoje
                    </x-button>
                </div>

                <div class="col-lg-6">
                    <div class="story-figure">
                        <x-logo variant="full" size="xl" class="brand-logo mx-auto mb-3" />
                        <p class="text-muted mb-0" style="font-size:.9375rem">
                            {{ config('bendito.contact.address') }}<br>
                            {{ config('bendito.contact.city') }}/{{ config('bendito.contact.state') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--muted">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow mb-2">No que acreditamos</span>
                    <h2>Nossos princípios</h2>
                    <p>Quatro compromissos que orientam tudo que plantamos e entregamos.</p>
                </div>
            </div>

            <div class="row g-4">
                @foreach ([
                    ['flower1', 'Manejo orgânico', 'Nenhum agrotóxico entra na horta. Adubação orgânica, rotação de culturas e controle natural de pragas.'],
                    ['people', 'Preço justo ao produtor', 'Vender direto elimina o atravessador — você paga menos e quem planta recebe mais.'],
                    ['recycle', 'Menos desperdício', 'Colhemos por demanda. O que é pedido hoje sai da terra hoje, sem estoque parado.'],
                    ['heart', 'Relação de perto', 'Você fala com a gente pelo WhatsApp e sabe exatamente de onde veio o seu alimento.'],
                ] as [$icon, $title, $text])
                    <div class="col-sm-6 col-lg-3">
                        <div class="value-card">
                            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section pt-0">
        <div class="container">
            <div class="cta-band">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <h2 class="mb-2">Quer conhecer a horta?</h2>
                        <p>Recebemos visitas com agendamento. Chame a gente e combinamos um dia.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <x-button href="{{ route('contact') }}" variant="light" size="lg" icon="chat-dots">
                            Falar conosco
                        </x-button>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

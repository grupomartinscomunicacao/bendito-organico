@extends('layouts.app')

@section('title', 'Contato')
@section('description', 'Fale com o Bendito Orgânico: dúvidas sobre produtos, entregas e pedidos. Atendimento por WhatsApp, e-mail e telefone.')

@section('content')

    <section class="page-hero">
        {{-- A mesma foto da home, aqui só como textura de fundo: a altura da
             faixa não muda, e o véu do CSS mantém o contraste do texto. --}}
        <x-hero-media />

        <div class="container">
            <nav aria-label="Você está aqui">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Início</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Contato</li>
                </ol>
            </nav>

            <h1 class="display-brand mb-2">Fale com a gente</h1>
            <p style="max-width: 48ch">Dúvida sobre um produto, uma entrega ou o seu pedido? Respondemos rápido.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="row g-5">

                {{-- Channels --}}
                <div class="col-lg-5">
                    <span class="eyebrow mb-3">Canais de atendimento</span>
                    <h2 class="h3 mb-4">Escolha como prefere falar</h2>

                    <div class="d-grid gap-3">
                        @if ($whatsapp = config('bendito.contact.whatsapp'))
                            <a href="https://wa.me/{{ $whatsapp }}" class="contact-card text-decoration-none" target="_blank" rel="noopener">
                                <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                <div>
                                    <strong>WhatsApp</strong>
                                    <span>{{ config('bendito.contact.phone') }} — o jeito mais rápido</span>
                                </div>
                            </a>
                        @endif

                        <div class="contact-card">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <div>
                                <strong>E-mail</strong>
                                <a href="mailto:{{ config('bendito.contact.email') }}">{{ config('bendito.contact.email') }}</a>
                            </div>
                        </div>

                        <div class="contact-card">
                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            <div>
                                <strong>Onde plantamos</strong>
                                <span>
                                    {{ config('bendito.contact.address') }}<br>
                                    {{ config('bendito.contact.city') }}/{{ config('bendito.contact.state') }}
                                </span>
                            </div>
                        </div>

                        <div class="contact-card" id="meu-pedido">
                            <i class="bi bi-bag-check" aria-hidden="true"></i>
                            <div class="flex-grow-1">
                                <strong>Acompanhar um pedido</strong>
                                <span class="d-block mb-2">Informe o telefone que você usou na compra.</span>

                                {{-- Mesmo fluxo do "Meu pedido" da navbar: o
                                     telefone é o identificador do cliente. --}}
                                <form method="POST" action="{{ route('orders.lookup.search') }}" class="d-flex gap-2">
                                    @csrf
                                    <input
                                        type="tel"
                                        name="telefone"
                                        class="form-control"
                                        placeholder="(00) 00000-0000"
                                        inputmode="numeric"
                                        autocomplete="tel"
                                        data-mask="phone"
                                        aria-label="Telefone do pedido"
                                        required
                                    >
                                    <button type="submit" class="btn btn-primary text-nowrap">Buscar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form --}}
                <div class="col-lg-7">
                    <div class="checkout-panel">
                        <header class="checkout-panel__head">
                            <span class="step-marker"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                            <div>
                                <h2>Mande uma mensagem</h2>
                                <p>Respondemos em até um dia útil.</p>
                            </div>
                        </header>

                        <form method="POST" action="{{ route('contact.send') }}" novalidate>
                            @csrf

                            {{-- Honeypot: hidden from people, irresistible to bots. --}}
                            <div class="hp-field" aria-hidden="true">
                                <label for="website">Não preencha este campo</label>
                                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <x-form.input name="name" label="Seu nome" autocomplete="name" icon="person" required />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input name="email" type="email" label="E-mail" autocomplete="email" icon="envelope" required />
                                </div>
                                <div class="col-md-6">
                                    <x-form.input
                                        name="phone"
                                        type="tel"
                                        label="Telefone (opcional)"
                                        autocomplete="tel"
                                        icon="telephone"
                                        data-mask="phone"
                                    />
                                </div>
                            </div>

                            <x-form.textarea
                                name="message"
                                label="Mensagem"
                                rows="5"
                                placeholder="Como podemos ajudar?"
                                maxlength="2000"
                                required
                            />

                            <div class="d-grid d-sm-block">
                                <x-button variant="primary" size="lg" icon="send">Enviar mensagem</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

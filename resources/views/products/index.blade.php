@extends('layouts.app')

@section('title', 'Produtos orgânicos')
@section('description', 'Catálogo completo de hortaliças, verduras, legumes e temperos orgânicos do Bendito Orgânico. Colhidos no dia e entregues frescos.')

@section('content')

    <section class="page-hero">
        {{-- A mesma foto da home, aqui só como textura de fundo: a altura da
             faixa não muda, e o véu do CSS mantém o contraste do texto. --}}
        <x-hero-media />

        <div class="container">
            <nav aria-label="Você está aqui">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Início</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Produtos</li>
                </ol>
            </nav>

            <h1 class="display-brand mb-2">Nosso catálogo</h1>
            <p>Tudo que está saindo da horta esta semana.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">

            @if ($minimumOrder > 0)
                <div class="catalog-notice">
                    <i class="bi bi-basket2" aria-hidden="true"></i>
                    <span>
                        <strong>Pedido mínimo de {{ \App\Support\Money::brl($minimumOrder) }}.</strong>
                        A quantidade sugerida já vem preenchida na página de cada produto.
                    </span>
                </div>
            @endif

            <div class="catalog-toolbar">
                <form method="GET" action="{{ route('products.index') }}" class="d-flex flex-wrap gap-2 flex-grow-1">
                    {{-- Busca escondida no celular: o catálogo é curto o bastante para
                         ser percorrido com o polegar, e a barra custava uma dobra inteira
                         antes do primeiro produto aparecer. No desktop ela continua. --}}
                    <div class="input-group d-none d-md-flex" style="max-width: 24rem">
                        <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            class="form-control"
                            placeholder="Buscar por nome…"
                            aria-label="Buscar produtos"
                        >
                    </div>

                    <select name="ordenar" class="form-select" style="max-width: 12rem" aria-label="Ordenar por" onchange="this.form.submit()">
                        <option value="">Ordem sugerida</option>
                        <option value="preco" @selected(request('ordenar') === 'preco')>Menor preço</option>
                    </select>

                    <button type="submit" class="btn btn-primary d-none d-md-inline-flex">Buscar</button>

                    {{-- O "Limpar" continua no celular quando há busca ativa: sem o campo
                         visível, seria a única saída de um resultado filtrado. --}}
                    @if ($search !== '' || request('ordenar'))
                        <a href="{{ route('products.index') }}" class="btn btn-link">Limpar</a>
                    @endif
                </form>

                {{-- Str::plural() é do inglês e devolvia "14 items" nesta linha. --}}
                <span class="text-muted text-nowrap" style="font-size: .9375rem">
                    {{ $products->total() }} {{ $products->total() === 1 ? 'item' : 'itens' }}
                </span>
            </div>

            @if ($products->isEmpty())
                <x-empty-state icon="search" title="Nenhum produto encontrado">
                    @if ($search !== '')
                        Não encontramos nada para “{{ $search }}”. Tente outro termo.
                    @else
                        Ainda não há produtos disponíveis. Volte em breve!
                    @endif

                    <x-slot:action>
                        <x-button href="{{ route('products.index') }}" variant="primary" icon="arrow-counterclockwise">
                            Ver todos os produtos
                        </x-button>
                    </x-slot:action>
                </x-empty-state>
            @else
                <div class="row g-4">
                    @foreach ($products as $product)
                        <div class="col-6 col-lg-4 col-xl-3">
                            <x-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 d-flex justify-content-center">
                    {{ $products->links() }}
                </div>
            @endif

        </div>
    </section>

@endsection

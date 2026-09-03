<nav class="navbar navbar-expand-lg site-navbar sticky-top" aria-label="Navegação principal">
    <div class="container">
        {{-- The official lockup, knocked out to white for the dark bar. --}}
        <a class="navbar-brand" href="{{ route('home') }}" aria-label="{{ config('bendito.name') }} — início">
            <x-logo variant="light" class="brand-logo" />
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarPrincipal"
            aria-controls="navbarPrincipal"
            aria-expanded="false"
            aria-label="Abrir menu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarPrincipal">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link @if (request()->routeIs('home')) active @endif"
                       href="{{ route('home') }}"
                       @if (request()->routeIs('home')) aria-current="page" @endif>Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if (request()->routeIs('products.*')) active @endif"
                       href="{{ route('products.index') }}"
                       @if (request()->routeIs('products.*')) aria-current="page" @endif>Produtos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if (request()->routeIs('about')) active @endif"
                       href="{{ route('about') }}">Sobre nós</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if (request()->routeIs('contact')) active @endif"
                       href="{{ route('contact') }}">Contato</a>
                </li>

                {{-- Leva para a busca por telefone: o cliente não precisa
                     guardar nenhum código para achar o próprio pedido. --}}
                <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                    <a
                        href="{{ route('orders.lookup') }}"
                        class="btn navbar-cta d-inline-flex align-items-center justify-content-center gap-2"
                        @if (request()->routeIs('orders.lookup')) aria-current="page" @endif
                    >
                        <i class="bi bi-bag-check" aria-hidden="true"></i>
                        <span>Meu pedido</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

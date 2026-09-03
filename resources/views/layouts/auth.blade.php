<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#11411B">

    <title>@yield('title', 'Painel') — {{ config('bendito.name') }}</title>

    <link rel="icon" href="{{ asset('images/brand/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="auth-shell">

        {{-- Brand column, hidden on small screens where it would only push the form down. --}}
        <aside class="auth-aside">
            <x-logo variant="light" size="lg" class="brand-logo" />

            <h2 class="text-balance">Painel administrativo do {{ config('bendito.name') }}</h2>
            <p>Gerencie o catálogo, acompanhe pedidos e confira os pagamentos em um só lugar.</p>

            <ul>
                <li><i class="bi bi-box-seam" aria-hidden="true"></i> Catálogo de produtos e estoque</li>
                <li><i class="bi bi-receipt" aria-hidden="true"></i> Pedidos e cartão de separação</li>
                <li><i class="bi bi-credit-card-2-front" aria-hidden="true"></i> Conciliação com o Mercado Pago</li>
            </ul>
        </aside>

        <main class="auth-panel">
            <div class="auth-card">
                <header class="auth-card__head">
                    {{-- On the light panel, the official dark lockup is the right version. --}}
                    <x-logo variant="full" size="lg" class="brand-logo mx-auto d-lg-none" />
                    <h1>@yield('heading', 'Entrar no painel')</h1>
                    <p>@yield('subheading')</p>
                </header>

                <x-flash />

                @yield('content')

                <footer class="auth-card__foot">
                    <a href="{{ route('home') }}" class="link-quiet">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Voltar para a loja
                    </a>
                </footer>
            </div>
        </main>
    </div>
</body>
</html>

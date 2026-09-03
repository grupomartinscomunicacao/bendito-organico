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

    @stack('head')
</head>
<body class="admin-shell">

    {{-- Fixed rail from lg up; an offcanvas drawer below that. --}}
    <aside class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar" aria-label="Menu do painel">
        @include('partials.admin.sidebar')
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button
                class="btn btn-light d-lg-none"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#adminSidebar"
                aria-controls="adminSidebar"
                aria-label="Abrir menu"
            >
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <h1 class="admin-topbar__title">@yield('title', 'Painel')</h1>

            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-2" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    Ver loja
                </a>

                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            {{ auth()->user()->email }}<br>
                            <span class="text-muted">{{ auth()->user()->role->label() }}</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        @can('viewAny', App\Models\User::class)
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.users.edit', auth()->user()) }}">
                                    <i class="bi bi-person-gear me-2" aria-hidden="true"></i>Minha conta
                                </a>
                            </li>
                        @endcan
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sair
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <x-flash />

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $pageTitle = trim($__env->yieldContent('title'));
    $pageDescription = trim($__env->yieldContent('description')) ?: config('bendito.description');
    $pageImage = trim($__env->yieldContent('og_image')) ?: asset(config('bendito.logo.og'));

    $metaTitle = $pageTitle !== ''
        ? $pageTitle.' — '.config('bendito.name')
        : config('bendito.name').' — '.config('bendito.tagline');
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#11411B">

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ config('bendito.name') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="pt_BR">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="{{ asset('images/brand/favicon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="d-flex flex-column min-vh-100">
    <a href="#conteudo" class="visually-hidden-focusable btn btn-primary m-2 position-absolute" style="z-index:1080">
        Ir para o conteúdo
    </a>

    @include('partials.navbar')

    <main id="conteudo" class="flex-grow-1">
        @if (session()->hasAny(['success', 'error', 'warning', 'info']) || $errors->any())
            <div class="container pt-4">
                <x-flash />
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.footer')

    @if ($whatsapp = config('bendito.contact.whatsapp'))
        <a
            href="https://wa.me/{{ $whatsapp }}"
            class="whatsapp-float"
            target="_blank"
            rel="noopener"
            aria-label="Falar no WhatsApp"
        >
            <i class="bi bi-whatsapp" aria-hidden="true"></i>
        </a>
    @endif

    @stack('scripts')
</body>
</html>

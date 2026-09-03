<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Impressão') — {{ config('bendito.name') }}</title>

    <link rel="icon" href="{{ asset('images/brand/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/scss/app.scss'])
</head>
{{-- No navbar, no sidebar, no menus: this document only ever contains the card. --}}
<body class="print-page">
    @yield('content')

    <script>
        // Opening the card with ?auto=1 sends it straight to the print dialog,
        // so the operator goes from the order list to paper in one click.
        if (new URLSearchParams(window.location.search).get('auto') === '1') {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>

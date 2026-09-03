@props([
    'title' => null,
    'flush' => false,
])

<section {{ $attributes->merge(['class' => 'admin-card']) }}>
    @if ($title || isset($actions))
        <header class="admin-card__head">
            @if ($title)
                <h2>{{ $title }}</h2>
            @endif

            @isset($actions)
                <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    @if ($flush)
        {{ $slot }}
    @else
        <div class="admin-card__body">{{ $slot }}</div>
    @endif

    @isset($footer)
        <footer class="admin-card__foot">{{ $footer }}</footer>
    @endisset
</section>

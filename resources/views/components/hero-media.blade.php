{{--
    Foto de capa do hero, em variantes responsivas.

    Extraída para um componente porque aparece na home e nos cabeçalhos das
    páginas internas — e as duas precisam do mesmo srcset para reaproveitar o
    arquivo já baixado quando o visitante navega de uma para a outra.

    "priority" só na home: lá a foto é o maior elemento da primeira dobra (o
    LCP). Nos cabeçalhos internos ela é decorativa e não deve competir com o
    conteúdo pela banda.
--}}

@props(['priority' => false])

<picture {{ $attributes->merge(['class' => 'hero__media']) }}>
    <source
        type="image/webp"
        srcset="{{ asset(config('bendito.hero.webp_768')) }} 768w,
                {{ asset(config('bendito.hero.webp_1280')) }} 1280w,
                {{ asset(config('bendito.hero.webp')) }} 1920w"
        sizes="100vw"
    >
    <img
        src="{{ asset(config('bendito.hero.jpg')) }}"
        alt=""
        width="1920"
        height="1080"
        @if ($priority)
            fetchpriority="high"
        @else
            loading="lazy"
        @endif
        decoding="async"
    >
</picture>

@props(['name'])

{{--
    Ícones dos passos do "Como funciona".

    SVG inline, e não o Bootstrap Icons que o resto do site usa, por dois
    motivos: aqui eles são o elemento gráfico principal da seção e precisam de
    traço fino e uniforme (o conjunto do Bootstrap mistura preenchido e
    contornado), e inline eles herdam `currentColor` — o mesmo desenho serve
    o card claro e qualquer fundo que venha depois, sem uma segunda cópia.

    Todos partem do mesmo grid 24×24 com traço 1.5, então alinham entre si.
--}}

@php
    $paths = match ($name) {
        // Cesta de compras — escolher o produto no catálogo.
        'basket' => '<path d="M4 9h16l-1.5 9.1a2 2 0 0 1-2 1.7H7.5a2 2 0 0 1-2-1.7L4 9Z"/>
                     <path d="M8.2 9 11 3.8M15.8 9 13 3.8"/>
                     <path d="M9.6 12.8v3.4M14.4 12.8v3.4"/>',

        // O próprio seletor de quantidade: menos, valor, mais.
        'stepper' => '<rect x="2.5" y="7.5" width="19" height="9" rx="2.5"/>
                      <path d="M6 12h2.4"/>
                      <path d="M15.6 12H18M16.8 10.8v2.4"/>
                      <path d="M11.9 9.9v4.2M11.9 9.9l-1.2 1"/>',

        // Formulário de dados e endereço.
        'form' => '<path d="M14 3H6.5A1.5 1.5 0 0 0 5 4.5v15A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5V8l-5-5Z"/>
                   <path d="M14 3v5h5"/>
                   <path d="M8.5 12.5h7M8.5 16h4.5"/>',

        // Escudo com visto — pagamento protegido.
        'shield' => '<path d="M12 21s7-3.2 7-9V6.2l-7-3-7 3V12c0 5.8 7 9 7 9Z"/>
                     <path d="m9 12 2.2 2.2L15.4 10"/>',

        default => '',
    };
@endphp

<svg
    {{ $attributes->merge(['class' => 'step-card__glyph']) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.5"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>{!! $paths !!}</svg>

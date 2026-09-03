@props([
    // full  → official horizontal lockup, dark wordmark (light backgrounds)
    // light → same lockup knocked out to white (dark backgrounds)
    // mark  → circular badge only (tight spaces, avatars, favicons)
    'variant' => 'full',
    'size' => null,
    'alt' => null,
])

@php
    $sources = [
        'full' => config('bendito.logo.full'),
        'light' => config('bendito.logo.light'),
        'mark' => config('bendito.logo.mark'),
    ];

    $source = $sources[$variant] ?? $sources['full'];

    $sizeClass = match ($size) {
        'sm' => 'brand-logo--sm',
        'lg' => 'brand-logo--lg',
        'xl' => 'brand-logo--xl',
        default => '',
    };

    // width:auto on .brand-logo keeps the official artwork in its original
    // proportions no matter which height a context asks for.
    $base = $variant === 'mark' ? 'brand-mark' : 'brand-logo';
@endphp

<img
    src="{{ asset($source) }}"
    alt="{{ $alt ?? config('bendito.name') }}"
    {{ $attributes->merge(['class' => trim("{$base} {$sizeClass}")]) }}
    decoding="async"
>

@props([
    'variant' => 'primary',
    'size' => null,
    'href' => null,
    'icon' => null,
    'iconAfter' => null,
    'type' => 'submit',
])

@php
    $classes = trim(implode(' ', array_filter([
        'btn',
        "btn-{$variant}",
        $size ? "btn-{$size}" : null,
        'd-inline-flex align-items-center justify-content-center gap-2',
    ])));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<i class="bi bi-{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
        @if ($iconAfter)<i class="bi bi-{{ $iconAfter }}" aria-hidden="true"></i>@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<i class="bi bi-{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
        @if ($iconAfter)<i class="bi bi-{{ $iconAfter }}" aria-hidden="true"></i>@endif
    </button>
@endif

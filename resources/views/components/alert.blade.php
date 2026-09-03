@props([
    'type' => 'info',
    'dismissible' => true,
    'icon' => null,
])

@php
    $icons = [
        'success' => 'check-circle-fill',
        'error' => 'exclamation-octagon-fill',
        'danger' => 'exclamation-octagon-fill',
        'warning' => 'exclamation-triangle-fill',
        'info' => 'info-circle-fill',
    ];

    // "error" is the flash key the controllers use; Bootstrap calls it danger.
    $variant = $type === 'error' ? 'danger' : $type;
    $glyph = $icon ?? ($icons[$type] ?? 'info-circle-fill');
@endphp

<div
    {{ $attributes->merge(['class' => "alert alert-{$variant} js-autodismiss" . ($dismissible ? ' alert-dismissible fade show' : '')]) }}
    role="alert"
>
    <i class="bi bi-{{ $glyph }}" aria-hidden="true"></i>
    <div class="flex-grow-1">{{ $slot }}</div>

    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    @endif
</div>

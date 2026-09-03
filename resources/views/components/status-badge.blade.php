@props([
    // Accepts an OrderStatus, a PaymentStatus or a UserRole — every one of
    // them exposes label(), variant() and (optionally) icon().
    'status',
    'soft' => false,
    'showIcon' => true,
])

@php
    $variant = $status->variant();
    $icon = method_exists($status, 'icon') ? $status->icon() : null;
@endphp

<span {{ $attributes->merge([
    'class' => $soft
        ? "badge-soft badge-soft--{$variant}"
        : "badge text-bg-{$variant} d-inline-flex align-items-center gap-1",
]) }}>
    @if ($showIcon && $icon)
        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $status->label() }}
</span>

@props([
    'label',
    'value',
    'icon' => 'graph-up',
    'variant' => 'primary',
    'hint' => null,
    'href' => null,
])

<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    <span class="stat-card__icon stat-card__icon--{{ $variant }}">
        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    </span>

    <p class="stat-card__label">{{ $label }}</p>
    <p class="stat-card__value mb-0">{{ $value }}</p>

    @if ($hint)
        <p class="stat-card__hint mb-0">{{ $hint }}</p>
    @endif

    @if ($href)
        <a href="{{ $href }}" class="stat-card__link" aria-label="{{ $label }}"></a>
    @endif
</div>

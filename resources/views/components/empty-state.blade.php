@props([
    'icon' => 'inbox',
    'title' => 'Nada por aqui ainda',
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <i class="bi bi-{{ $icon }} d-block mb-3" aria-hidden="true"></i>
    <h3 class="h5 mb-2">{{ $title }}</h3>

    @if (trim($slot) !== '')
        <p class="text-muted mb-0">{{ $slot }}</p>
    @endif

    @isset($action)
        <div class="mt-3">{{ $action }}</div>
    @endisset
</div>

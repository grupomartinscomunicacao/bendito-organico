{{-- Renders whatever the previous request flashed, in a stable order. --}}
@foreach (['success', 'error', 'warning', 'info'] as $type)
    @if (session()->has($type))
        <x-alert :type="$type">{{ session($type) }}</x-alert>
    @endif
@endforeach

@if ($errors->any() && ! $errors->has('__none'))
    <x-alert type="danger">
        <strong class="d-block mb-1">Revise os campos destacados.</strong>
        <ul class="mb-0 ps-3">
            @foreach ($errors->unique() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif

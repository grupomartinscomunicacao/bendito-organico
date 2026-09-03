@extends('layouts.admin')

@section('title', 'Editar produto')

@section('content')

    <x-admin.page-head :title="$product->name" subtitle="Editar produto do catálogo.">
        <x-slot:actions>
            <x-button href="{{ route('products.show', $product) }}" variant="outline-secondary" icon="box-arrow-up-right" target="_blank" rel="noopener">
                Ver na loja
            </x-button>
            <x-button href="{{ route('admin.products.index') }}" variant="outline-secondary" icon="arrow-left">
                Voltar
            </x-button>
        </x-slot:actions>
    </x-admin.page-head>

    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        @include('admin.products._form', ['product' => $product, 'units' => $units])
    </form>

@endsection

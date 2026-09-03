@extends('layouts.admin')

@section('title', 'Novo produto')

@section('content')

    <x-admin.page-head title="Novo produto" subtitle="Cadastre um item do catálogo.">
        <x-slot:actions>
            <x-button href="{{ route('admin.products.index') }}" variant="outline-secondary" icon="arrow-left">
                Voltar
            </x-button>
        </x-slot:actions>
    </x-admin.page-head>

    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @include('admin.products._form', ['product' => $product, 'units' => $units])
    </form>

@endsection

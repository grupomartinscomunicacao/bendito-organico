@extends('layouts.admin')

@section('title', 'Editar usuário')

@section('content')

    <x-admin.page-head :title="$user->name" subtitle="Editar acesso ao painel.">
        <x-slot:actions>
            <x-button href="{{ route('admin.users.index') }}" variant="outline-secondary" icon="arrow-left">
                Voltar
            </x-button>
        </x-slot:actions>
    </x-admin.page-head>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.users._form', ['user' => $user, 'roles' => $roles])
    </form>

@endsection

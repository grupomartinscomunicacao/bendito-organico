@extends('layouts.admin')

@section('title', 'Novo usuário')

@section('content')

    <x-admin.page-head title="Novo usuário" subtitle="Dê acesso ao painel a um membro da equipe.">
        <x-slot:actions>
            <x-button href="{{ route('admin.users.index') }}" variant="outline-secondary" icon="arrow-left">
                Voltar
            </x-button>
        </x-slot:actions>
    </x-admin.page-head>

    <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
        @csrf
        @include('admin.users._form', ['user' => $user, 'roles' => $roles])
    </form>

@endsection

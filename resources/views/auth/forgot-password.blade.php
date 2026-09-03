@extends('layouts.auth')

@section('title', 'Recuperar senha')
@section('heading', 'Esqueceu a senha?')
@section('subheading', 'Enviamos um link de redefinição para o seu e-mail.')

@section('content')
    <form method="POST" action="{{ route('admin.password.email') }}">
        @csrf

        <x-form.input
            name="email"
            type="email"
            label="E-mail cadastrado"
            autocomplete="username"
            icon="envelope"
            autofocus
            required
        />

        <div class="d-grid">
            <x-button variant="primary" size="lg" icon="send">Enviar link</x-button>
        </div>
    </form>

    <p class="text-center mt-4 mb-0" style="font-size:.9375rem">
        <a href="{{ route('admin.login') }}">Voltar para o login</a>
    </p>
@endsection

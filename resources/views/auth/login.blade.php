@extends('layouts.auth')

@section('title', 'Entrar')
@section('heading', 'Entrar no painel')
@section('subheading', 'Acesso restrito à equipe do Bendito Orgânico.')

@section('content')
    <form method="POST" action="{{ route('admin.login.store') }}">
        @csrf

        <x-form.input
            name="email"
            type="email"
            label="E-mail"
            placeholder="voce@benditoorganico.com.br"
            autocomplete="username"
            icon="envelope"
            autofocus
            required
        />

        <div class="mb-3">
            <label class="form-label" for="password">Senha</label>
            <div class="input-with-action">
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control @error('password') is-invalid @enderror"
                    autocomplete="current-password"
                    required
                >
                <button type="button" class="password-toggle" data-password-toggle aria-label="Mostrar senha">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>

                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input type="checkbox" name="remember" id="remember" value="1" class="form-check-input" @checked(old('remember'))>
                <label class="form-check-label" for="remember">Continuar conectado</label>
            </div>

            <a href="{{ route('admin.password.request') }}" style="font-size:.875rem">Esqueci a senha</a>
        </div>

        <div class="d-grid">
            <x-button variant="primary" size="lg" icon="box-arrow-in-right">Entrar</x-button>
        </div>
    </form>
@endsection

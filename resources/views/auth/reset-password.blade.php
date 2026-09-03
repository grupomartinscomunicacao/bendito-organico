@extends('layouts.auth')

@section('title', 'Redefinir senha')
@section('heading', 'Definir nova senha')
@section('subheading', 'Escolha uma senha forte que você não usa em outro lugar.')

@section('content')
    <form method="POST" action="{{ route('admin.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-form.input
            name="email"
            type="email"
            label="E-mail"
            :value="$email"
            autocomplete="username"
            icon="envelope"
            required
        />

        <div class="mb-3">
            <label class="form-label" for="password">Nova senha</label>
            <div class="input-with-action">
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control @error('password') is-invalid @enderror"
                    autocomplete="new-password"
                    required
                >
                <button type="button" class="password-toggle" data-password-toggle aria-label="Mostrar senha">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>

                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <small class="form-hint">Mínimo de 8 caracteres.</small>
        </div>

        <x-form.input
            name="password_confirmation"
            type="password"
            label="Confirme a nova senha"
            autocomplete="new-password"
            required
        />

        <div class="d-grid">
            <x-button variant="primary" size="lg" icon="check2-circle">Redefinir senha</x-button>
        </div>
    </form>
@endsection

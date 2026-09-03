@extends('layouts.app')

@section('title', 'Sua sessão expirou')

@section('content')
    @include('errors.partials.message', [
        'code' => '419',
        'heading' => 'Sua sessão expirou',
        'message' => 'A página ficou aberta tempo demais. Recarregue e tente novamente.',
    ])
@endsection

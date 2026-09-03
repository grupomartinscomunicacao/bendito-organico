@extends('layouts.app')

@section('title', 'Acesso negado')

@section('content')
    @include('errors.partials.message', [
        'code' => '403',
        'heading' => 'Acesso negado',
        'message' => 'Você não tem permissão para ver esta página.',
    ])
@endsection

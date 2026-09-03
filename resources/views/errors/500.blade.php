@extends('layouts.app')

@section('title', 'Algo deu errado por aqui')

@section('content')
    @include('errors.partials.message', [
        'code' => '500',
        'heading' => 'Algo deu errado por aqui',
        'message' => 'Já fomos avisados e estamos resolvendo. Tente novamente em alguns minutos.',
    ])
@endsection

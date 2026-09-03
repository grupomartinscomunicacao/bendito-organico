@extends('layouts.app')

@section('title', 'Calma aí!')

@section('content')
    @include('errors.partials.message', [
        'code' => '429',
        'heading' => 'Calma aí!',
        'message' => 'Você fez muitas solicitações em pouco tempo. Aguarde um instante e tente de novo.',
    ])
@endsection

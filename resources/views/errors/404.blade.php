@extends('layouts.app')

@section('title', 'Página não encontrada')

@section('content')
    @include('errors.partials.message', [
        'code' => '404',
        'heading' => 'Página não encontrada',
        'message' => 'Esta página mudou de lugar ou nunca existiu. Que tal dar uma olhada no que colhemos hoje?',
    ])
@endsection

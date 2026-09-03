@extends('layouts.app')

@section('title', 'Voltamos já')

@section('content')
    @include('errors.partials.message', [
        'code' => '503',
        'heading' => 'Voltamos já',
        'message' => 'Estamos em manutenção rápida. Em instantes tudo volta ao normal.',
    ])
@endsection

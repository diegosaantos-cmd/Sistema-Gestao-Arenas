@extends('layouts.main')

@section('title', 'Termos de Uso')

@section('content')
<div class="container py-5">
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 800px;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <a href="{{ url('/') }}" class="logo-marca">ArenaPlay</a>
            </div>
            <div class="prose">
                {!! $terms !!}
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.main')

@section('title', 'Minhas Arenas')

@section('content')

<div class="container py-4">

    <h1 class="mb-4">Gerenciar Arenas</h1>

    <!-- CARDS -->
    <div class="row mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Arenas</h5>
                    <h1>{{ auth()->user()->arenas()->count() }}</h1>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Quadras</h5>
                    <h1>{{ $totalQuadras ?? 0 }}</h1>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Agendamentos Hoje</h5>
                    <h1>{{ $agendamentosHoje ?? 0 }}</h1>
                </div>
            </div>
        </div>

    </div>

    <!-- BOTÃO NOVA ARENA -->
    <div class="mb-4">
        <a href="{{ route('arenas.create') }}" class="btn btn-primary">
            Nova Arena
        </a>
    </div>

    <!-- LISTA DE ARENAS -->
    <div class="card shadow-sm">
        <div class="card-body">

            @foreach($arenas as $arena)

                <div class="border rounded p-3 mb-3">

                    <h5>{{ $arena->name }}</h5>

                    <p>{{ $arena->address_rua }}, {{ $arena->address_numero }} - {{ $arena->address_bairro }}</p>

                    <a href="{{ route('arenas.edit', $arena->id) }}" class="btn btn-primary">
                        Gerenciar
                    </a>

                </div>

            @endforeach

        </div>
    </div>

</div>

@endsection
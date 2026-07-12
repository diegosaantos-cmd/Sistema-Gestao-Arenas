@extends('layouts.main')

@section('title', 'Nova Quadra')

@section('content')

<div class="container py-5 painel">

    <div class="card shadow mx-auto" style="max-width: 900px;">
        <div class="card-body p-4">

            <h1 class="h3 fw-bold mb-1">Nova quadra</h1>
            <p class="text-muted">Cadastre uma ou várias quadras de uma vez.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Não foi possível salvar.</strong> Corrija o que está marcado abaixo:
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('quadras.store') }}" method="POST">
                @csrf

                <div class="alert alert-light border">
                    <i class="bi bi-building me-1"></i>
                    Adicionando quadra(s) à arena <strong>{{ $arena->name }}</strong>
                </div>

                @include('arenas.partials.courts')

                <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-4">
                    <a href="{{ route('owners.dashboard') }}" class="btn btn-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Salvar
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

@endsection

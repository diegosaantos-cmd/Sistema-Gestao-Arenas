@extends('layouts.main')

@section('title', 'Nova Quadra')

@section('content')

<div class="container py-5">

    <div class="card shadow mx-auto" style="max-width: 900px;">
        <div class="card-body p-4">

            <h2 class="mb-4">Nova Quadra</h2>

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

                <p class="fs-5 mb-3">
                    🏟 Adicionando quadra(s) à arena:
                    <strong>{{ $arena->name }}</strong>
                </p>

                @include('arenas.partials.courts')

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('owners.dashboard') }}"
                       class="btn btn-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-success">
                        Salvar
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

@endsection

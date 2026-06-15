@extends('layouts.main')

@section('title', 'Escolher Arena')

@section('content')

<div class="container py-5">

    <div class="mb-4">
        <h1 class="fw-bold">Qual arena você quer gerenciar?</h1>
        <p class="text-muted fs-5">
            Você tem mais de uma arena. Escolha uma para abrir o painel dela
            (depois dá pra trocar a qualquer momento).
        </p>
    </div>

    <div class="row g-4">
        @foreach ($arenas as $arena)
            <div class="col-md-4">
                <form action="{{ route('owners.arena.select') }}" method="POST" class="h-100">
                    @csrf
                    <input type="hidden" name="arena_id" value="{{ $arena->id }}">

                    <button type="submit"
                            class="card shadow-sm border-0 w-100 h-100 text-start p-0 bg-white">
                        <div class="card-body">
                            <h4 class="fw-bold mb-1">{{ $arena->name }}</h4>
                            <p class="text-muted mb-0">
                                {{ $arena->address_rua }}, {{ $arena->address_numero }}
                                - {{ $arena->address_bairro }}
                            </p>
                        </div>
                    </button>
                </form>
            </div>
        @endforeach
    </div>

</div>

@endsection

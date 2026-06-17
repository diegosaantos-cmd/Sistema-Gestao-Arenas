@extends('layouts.main')

@section('title', 'Quadras da Arena')

@section('content')

<div class="container py-4">

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="{{ route('arenas.show', $arena->id) }}" class="btn btn-dark btn-sm">
            ← Voltar para a arena
        </a>
        <a href="{{ route('owners.dashboard') }}" class="btn btn-dark btn-sm">
            ← Voltar ao painel
        </a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="fw-bold mb-0">Quadras — {{ $arena->name }}</h1>
        <a href="{{ route('quadras.create') }}" class="btn btn-primary">
            + Nova Quadra
        </a>
    </div>

    @forelse ($courts as $court)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">

                <div>
                    <h5 class="fw-bold mb-1">
                        {{ $court->name }}
                        @if ($court->active)
                            <span class="badge bg-success align-middle">Ativa</span>
                        @else
                            <span class="badge bg-secondary align-middle">Inativa</span>
                        @endif
                    </h5>
                    <div class="text-muted small">
                        <div><strong>Valor/hora:</strong> R$ {{ number_format($court->hourly_rate, 2, ',', '.') }}</div>
                        <div>
                            <strong>Esportes:</strong>
                            @if ($court->sports->isNotEmpty())
                                @foreach ($court->sports as $s)
                                    {{ \App\Models\Court::SPORTS[$s->sport] ?? $s->sport }}@if(! $loop->last), @endif
                                @endforeach
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Botões sem ação por enquanto (lógica depois) --}}
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary">✏️ Editar</button>
                    <button type="button" class="btn btn-sm btn-outline-warning">
                        {{ $court->active ? '🚫 Desativar' : '✅ Reativar' }}
                    </button>
                </div>

            </div>
        </div>
    @empty
        <div class="alert alert-light border text-center text-muted">
            Nenhuma quadra cadastrada nesta arena.
        </div>
    @endforelse

</div>

@endsection

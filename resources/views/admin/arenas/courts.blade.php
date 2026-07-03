@extends('layouts.main')

@section('title', 'Quadras da Arena')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <a href="{{ route('admin.owners.arenas', $arena->owner) }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar às arenas
    </a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Quadras de {{ $arena->name }}</h1>
        <p class="dashboard-subtitle mb-0">
            {{ $arena->owner?->company_name }} · {{ $arena->address_rua }}, {{ $arena->address_numero }}
        </p>
    </div>

    <div class="row g-4">
        @forelse ($quadras as $quadra)
            <div class="col-md-6 col-xl-4">
                <div class="dashboard-box h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <h2 class="h4 fw-bold mb-0">{{ $quadra->name }}</h2>
                        <span class="badge {{ $quadra->active ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $quadra->active ? 'Ativa' : 'Desativada' }}
                        </span>
                    </div>

                    <p class="text-muted">{{ $quadra->description ?: 'Sem descrição.' }}</p>

                    <div class="mb-3">
                        <span class="text-muted small">Esportes</span>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @forelse ($quadra->sports as $sport)
                                <span class="badge bg-primary">{{ \App\Models\Court::SPORTS[$sport->sport] ?? $sport->sport }}</span>
                            @empty
                                <span class="text-muted small">Não informado</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="fs-4 fw-bold text-success mb-4">
                        R$ {{ number_format($quadra->hourly_rate, 2, ',', '.') }}/hora
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <button type="button" class="btn btn-warning btn-sm">
                            <i class="bi bi-power me-1"></i> Desativar
                        </button>
                        <button type="button" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash me-1"></i> Excluir
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="dashboard-box text-center text-muted">Esta arena não possui quadras.</div></div>
        @endforelse
    </div>
</div>

@endsection

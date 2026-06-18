@extends('layouts.main')

@section('title', 'Nova Reserva')

@section('content')

<div class="dashboard-container container-fluid py-4">

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('dashboard') }}" class="btn btn-dark btn-sm">
            ← Voltar ao painel
        </a>
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Nova Reserva</h1>
        <p class="dashboard-subtitle mb-0">Escolha uma arena para ver as quadras e reservar.</p>
    </div>

    <div class="row g-4">
        @forelse ($arenas as $arena)
            <div class="col-md-4">
                <div class="dashboard-box h-100 d-flex flex-column">
                    <h3 class="fw-bold mb-1">{{ $arena->name }}</h3>
                    <p class="text-muted mb-2">
                        <i class="bi bi-geo-alt me-1"></i>
                        {{ $arena->address_rua }}, {{ $arena->address_numero }} - {{ $arena->address_bairro }}
                    </p>
                    @if ($arena->description)
                        <p class="small mb-3">{{ $arena->description }}</p>
                    @endif
                    <a href="{{ route('client.arenas.show', $arena) }}" class="btn dashboard-btn-primary mt-auto">
                        <i class="bi bi-eye me-2"></i> Ver quadras
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="dashboard-box text-center text-muted">
                    Nenhuma arena disponível no momento.
                </div>
            </div>
        @endforelse
    </div>

</div>

@endsection

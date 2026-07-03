@extends('layouts.main')

@section('title', 'Arenas da Empresa')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.owners.index') }}" class="btn btn-dark btn-sm">← Voltar às empresas</a>
        @include('admin.owners._company-switcher')
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Arenas de {{ $owner->company_name }}</h1>
        <p class="dashboard-subtitle mb-0">
            Proprietário: {{ $owner->user?->name }} · {{ $owner->user?->email }}
        </p>
    </div>

    <div class="row g-4">
        @forelse ($arenas as $arena)
            <div class="col-lg-6">
                <div class="dashboard-box h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h2 class="h4 fw-bold">{{ $arena->name }}</h2>
                        <span class="badge {{ $arena->active ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $arena->active ? 'Ativa' : 'Desativada' }}
                        </span>
                    </div>

                    <div class="row g-2 small mb-4">
                        <div class="col-12"><span class="text-muted">Descrição:</span><br><strong>{{ $arena->description ?: '—' }}</strong></div>
                        <div class="col-sm-6"><span class="text-muted">Endereço:</span><br><strong>{{ $arena->address_rua }}, {{ $arena->address_numero }} — {{ $arena->address_bairro }}</strong></div>
                        <div class="col-sm-6"><span class="text-muted">Contato:</span><br><strong>{{ $arena->phone ?: '—' }}<br>{{ $arena->contact_email ?: '—' }}</strong></div>
                        <div class="col-sm-6"><span class="text-muted">Quadras:</span><br><strong>{{ $arena->courts_count }}</strong></div>
                        <div class="col-sm-6"><span class="text-muted">Funcionários:</span><br><strong>{{ $arena->employees_count }}</strong></div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.arenas.courts', $arena) }}" class="btn btn-primary">
                            <i class="bi bi-grid-3x3-gap me-2"></i> Ver quadras
                        </a>
                        <button type="button" class="btn btn-warning">
                            <i class="bi bi-power me-1"></i> Desativar
                        </button>
                        <button type="button" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Excluir
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="dashboard-box text-center text-muted">Esta empresa não possui arenas.</div></div>
        @endforelse
    </div>
</div>

@endsection

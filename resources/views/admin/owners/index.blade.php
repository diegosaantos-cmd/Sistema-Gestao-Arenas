@extends('layouts.main')

@section('title', 'Empresas e Proprietários')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm mb-3">← Voltar ao painel</a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Empresas / Proprietários</h1>
        <p class="dashboard-subtitle mb-0">Selecione uma empresa para consultar ou administrar.</p>
    </div>

    <div class="row g-4">
        @forelse ($proprietarios as $proprietario)
            <div class="col-6 col-lg-3">
                <div class="dashboard-box h-100 d-flex flex-column overflow-hidden p-0">
                    <div class="d-flex justify-content-between align-items-start gap-2 p-3"
                         style="background: #021B35;">
                        <h2 class="h5 fw-bold text-white mb-0">{{ $proprietario->company_name }}</h2>
                        @if ($proprietario->active)
                            <span class="badge bg-success">Ativa</span>
                        @elseif ($proprietario->deactivation_source === 'admin')
                            <span class="badge bg-danger">Desativada pelo administrador</span>
                        @else
                            <span class="badge bg-warning text-dark">Desativada pela empresa</span>
                        @endif
                    </div>

                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <div class="small mb-4">
                            <div class="mb-2">
                                <span class="text-muted">Proprietário</span><br>
                                <strong>{{ $proprietario->user?->name ?? '—' }}</strong>
                            </div>
                            <div>
                                <span class="text-muted">Criada em</span><br>
                                <strong>{{ optional($proprietario->created_at)->format('d/m/Y') ?? '—' }}</strong>
                            </div>
                        </div>

                        <a href="{{ route('admin.owners.show', $proprietario) }}"
                           class="btn btn-primary w-100 mt-auto">
                            <i class="bi bi-eye me-2"></i> Ver empresa
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="dashboard-box text-center text-muted">Nenhum proprietário cadastrado.</div>
            </div>
        @endforelse
    </div>
</div>

@endsection

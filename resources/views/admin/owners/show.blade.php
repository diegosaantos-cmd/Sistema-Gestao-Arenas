@extends('layouts.main')

@section('title', 'Detalhes da Empresa')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.owners.index') }}" class="btn btn-dark btn-sm">
            ← Voltar às empresas
        </a>
        @include('admin.owners._company-switcher')
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="dashboard-title mb-1">{{ $owner->company_name }}</h1>
            <p class="dashboard-subtitle mb-0">Informações completas e desempenho das arenas.</p>
        </div>
        <span class="badge fs-6 {{ $owner->user?->active ? 'bg-success' : 'bg-danger' }}">
            {{ $owner->user?->active ? 'Conta ativa' : 'Conta bloqueada' }}
        </span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <a href="{{ route('admin.owners.profile', $owner) }}"
               class="dashboard-card h-100 text-decoration-none text-body">
                <div>
                    <h5>Perfil da empresa</h5>
                    <h2><i class="bi bi-building-gear"></i></h2>
                </div>
                <i class="bi bi-chevron-right fs-3 text-primary"></i>
            </a>
        </div>

        @php
            $cards = [
                ['Arenas', $totais['arenas'], 'bi-buildings', 'primary'],
                ['Arenas ativas', $totais['arenas_ativas'], 'bi-check-circle', 'success'],
                ['Quadras', $totais['quadras'], 'bi-grid-3x3-gap', 'info'],
                ['Funcionários', $totais['funcionarios'], 'bi-person-badge', 'secondary'],
            ];
        @endphp

        @foreach ($cards as [$titulo, $valor, $icone, $cor])
            <div class="col-6 col-lg">
                <div class="dashboard-card h-100">
                    <div><h5>{{ $titulo }}</h5><h2>{{ $valor }}</h2></div>
                    <i class="bi {{ $icone }} dashboard-icon text-{{ $cor }}"></i>
                </div>
            </div>
        @endforeach
    </div>

    <div class="dashboard-box mb-4 border border-success">
        <div class="text-muted">Faturamento bruto de todas as arenas no mês</div>
        <div class="display-6 fw-bold text-success">
            R$ {{ number_format($totais['faturamento_mes'], 2, ',', '.') }}
        </div>
        <small class="text-muted">Competência: {{ now()->translatedFormat('F/Y') }}</small>
    </div>

    <div class="dashboard-box">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="section-title mb-0">Desempenho por arena</h2>
            <a href="{{ route('admin.owners.arenas', $owner) }}" class="btn btn-primary btn-sm">
                Ver arenas em cards
            </a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Arena</th>
                        <th>Status</th>
                        <th>Quadras</th>
                        <th>Funcionários</th>
                        <th>Faturamento no mês</th>
                        <th class="text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($arenas as $arena)
                        <tr>
                            <td>
                                <strong>{{ $arena->name }}</strong>
                                <div class="small text-muted">{{ $arena->address_bairro }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $arena->active ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $arena->active ? 'Ativa' : 'Desativada' }}
                                </span>
                            </td>
                            <td>{{ $arena->courts_count }}</td>
                            <td>{{ $arena->employees_count }}</td>
                            <td class="fw-semibold">R$ {{ number_format($arena->faturamento_mes, 2, ',', '.') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.arenas.courts', $arena) }}" class="btn btn-outline-primary btn-sm">
                                    Ver quadras
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Esta empresa não possui arenas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

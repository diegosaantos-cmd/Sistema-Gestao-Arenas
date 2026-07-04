@extends('layouts.main')

@section('title', 'Administração Geral')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <div class="mb-5">
        <span class="badge bg-danger mb-2">ADMINISTRADOR GERAL</span>
        <h1 class="dashboard-title mb-1">Painel administrativo</h1>
        <p class="dashboard-subtitle mb-0">Escolha uma área para gerenciar o sistema.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-4">
            <a href="{{ route('admin.owners.index') }}"
               class="dashboard-card h-100 text-decoration-none text-body">
                <div>
                    <h5>Empresas / Proprietários</h5>
                    <h2>{{ $resumo['proprietarios'] }}</h2>
                    <small class="text-muted">Ver empresas e suas arenas</small>
                </div>
                <i class="bi bi-buildings dashboard-icon text-primary"></i>
            </a>
        </div>

        <div class="col-md-6 col-xl-4">
            <a href="{{ route('admin.owners.index') }}"
               class="dashboard-card h-100 text-decoration-none text-body">
                <div>
                    <h5>Arenas</h5>
                    <h2>{{ $resumo['arenas'] }}</h2>
                    <small class="text-muted">{{ $resumo['arenas_ativas'] }} ativas</small>
                </div>
                <i class="bi bi-geo-alt dashboard-icon text-success"></i>
            </a>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="dashboard-card h-100">
                <div>
                    <h5>Clientes</h5>
                    <h2>{{ $resumo['clientes'] }}</h2>
                    <small class="text-muted">Gestão de usuários</small>
                </div>
                <i class="bi bi-people dashboard-icon text-warning"></i>
            </div>
        </div>

    </div>

    <div class="dashboard-box mt-4">
        <h2 class="section-title mb-3">Resumo rápido</h2>
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="border rounded p-3">
                    <div class="fs-3 fw-bold">{{ $resumo['quadras'] }}</div>
                    <div class="text-muted">Quadras</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-3">
                    <div class="fs-3 fw-bold">{{ $resumo['funcionarios'] }}</div>
                    <div class="text-muted">Funcionários</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-3">
                    <div class="fs-3 fw-bold">{{ $resumo['reservas_mes'] }}</div>
                    <div class="text-muted">Reservas no mês</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-3">
                    <div class="fs-5 fw-bold">R$ {{ number_format($resumo['faturamento_bruto'], 2, ',', '.') }}</div>
                    <div class="text-muted">Faturamento bruto</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

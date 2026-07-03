@extends('layouts.main')

@section('title', 'Perfil da Empresa')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.owners.show', $owner) }}" class="btn btn-dark btn-sm">
            ← Voltar à empresa
        </a>
        @include('admin.owners._company-switcher')
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Perfil da empresa</h1>
        <p class="dashboard-subtitle mb-0">{{ $owner->company_name }}</p>
    </div>

    <div class="dashboard-box">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h2 class="section-title mb-1">Dados da empresa</h2>
                @if ($owner->active)
                    <span class="badge bg-success">Empresa ativa</span>
                @elseif ($owner->deactivation_source === 'admin')
                    <span class="badge bg-danger">Desativada pelo administrador</span>
                @else
                    <span class="badge bg-warning text-dark">Desativada pela própria empresa</span>
                @endif
            </div>

            <div class="d-flex gap-2">
                @if ($owner->active)
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                            data-bs-target="#modalDesativarEmpresa">
                        <i class="bi bi-slash-circle me-1"></i> Desativar
                    </button>
                @else
                    <form method="POST" action="{{ route('admin.owners.activate', $owner) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle me-1"></i> Ativar
                        </button>
                    </form>
                @endif
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                        data-bs-target="#modalExcluirEmpresa">
                    <i class="bi bi-trash me-1"></i> Excluir
                </button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <span class="text-muted">Nome da empresa</span><br>
                <strong>{{ $owner->company_name }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Proprietário</span><br>
                <strong>{{ $owner->user?->name ?? '—' }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">CPF/CNPJ</span><br>
                <strong>{{ $owner->tax_id }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Cadastro</span><br>
                <strong>{{ optional($owner->created_at)->format('d/m/Y') ?? '—' }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">E-mail</span><br>
                <strong>{{ $owner->user?->email ?? '—' }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Telefone</span><br>
                <strong>{{ $owner->user?->phone ?: '—' }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Tipo da conta</span><br>
                <strong>Proprietário</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Quantidade de arenas</span><br>
                <strong>{{ $owner->arenas_count }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Total de quadras</span><br>
                <strong>{{ $totalQuadras }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Total de funcionários</span><br>
                <strong>{{ $totalFuncionarios }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Plano da plataforma</span><br>
                <strong>Plano percentual — 10%</strong>
                <div class="small text-muted">Sobre o faturamento bruto mensal</div>
            </div>
            @if (! $owner->active)
                <div class="col-md-4">
                    <span class="text-muted">Desativada por</span><br>
                    <strong>
                        {{ $owner->deactivation_source === 'admin' ? 'Administrador do sistema' : 'Própria empresa' }}
                    </strong>
                    @if ($owner->deactivatedBy)
                        <div class="small text-muted">{{ $owner->deactivatedBy->name }}</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <span class="text-muted">Data da desativação</span><br>
                    <strong>{{ optional($owner->deactivated_at)->format('d/m/Y H:i') ?? '—' }}</strong>
                </div>
            @endif
            <div class="col-md-4">
                <span class="text-muted">Política de Privacidade e Termos</span><br>
                @if ($owner->user?->terms_accepted_at)
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i> Aceitos
                    </span>
                    <div class="small text-muted mt-1">
                        {{ $owner->user->terms_accepted_at->format('d/m/Y H:i') }}
                    </div>
                @else
                    <span class="badge bg-warning text-dark">Aceite não registrado</span>
                @endif
            </div>
        </div>
    </div>

    <div class="dashboard-box mt-4">
        <h2 class="section-title mb-3">Endereços e horários das arenas</h2>

        @php
            $diasSemana = [
                0 => 'Domingo',
                1 => 'Segunda-feira',
                2 => 'Terça-feira',
                3 => 'Quarta-feira',
                4 => 'Quinta-feira',
                5 => 'Sexta-feira',
                6 => 'Sábado',
            ];
        @endphp

        <div class="row g-4">
            @forelse ($owner->arenas as $arena)
                <div class="col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <h3 class="h5 fw-bold mb-0">{{ $arena->name }}</h3>
                            <span class="badge {{ $arena->active ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $arena->active ? 'Ativa' : 'Desativada' }}
                            </span>
                        </div>

                        <p class="mb-3">
                            <i class="bi bi-geo-alt me-1 text-primary"></i>
                            {{ $arena->address_rua }}, {{ $arena->address_numero }}
                            — {{ $arena->address_bairro }}
                        </p>

                        <div class="small mb-3">
                            <i class="bi bi-people me-1 text-primary"></i>
                            <strong>{{ $arena->employees_count }}</strong>
                            {{ $arena->employees_count === 1 ? 'funcionário' : 'funcionários' }}
                        </div>

                        <h4 class="h6 fw-bold">Dias e horários de funcionamento</h4>
                        <ul class="list-group list-group-flush">
                            @foreach ($diasSemana as $numero => $nome)
                                @php
                                    $periodos = $arena->businessHours
                                        ->where('day_of_week', $numero)
                                        ->sortBy('opens_at');
                                @endphp
                                <li class="list-group-item px-0 py-1 d-flex justify-content-between gap-3">
                                    <span>{{ $nome }}</span>
                                    <span class="text-end">
                                        @forelse ($periodos as $periodo)
                                            <span class="d-block">
                                                {{ substr($periodo->opens_at, 0, 5) }}–{{ substr($periodo->closes_at, 0, 5) }}
                                            </span>
                                        @empty
                                            <span class="text-muted">Fechado</span>
                                        @endforelse
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted">Esta empresa não possui arenas.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="modal fade" id="modalDesativarEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.owners.deactivate', $owner) }}">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Desativar empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Deseja realmente desativar <strong>{{ $owner->company_name }}</strong>?
                    O proprietário perderá o acesso e todas as arenas e quadras serão desativadas.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Sim, desativar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExcluirEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.owners.destroy', $owner) }}">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Excluir empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        Deseja realmente excluir <strong>{{ $owner->company_name }}</strong>?
                    </div>
                    A empresa, o acesso do proprietário e suas arenas deixarão de aparecer no sistema.
                    O histórico de reservas e financeiro será preservado.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Sim, excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@extends('layouts.main')

@section('title', 'Detalhes da Arena')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.owners.show', [$arena->owner, 'arenas_modal' => 1]) }}" class="btn btn-dark btn-sm">
            ← Voltar às arenas
        </a>
        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2">
            @if ($arena->active)
                <button type="button" class="btn btn-warning btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalDesativarArena">
                    <i class="bi bi-power me-1"></i> Desativar
                </button>
            @else
                <form method="POST" action="{{ route('admin.arenas.activate', $arena) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle me-1"></i> Ativar
                    </button>
                </form>
            @endif
            <button type="button" class="btn btn-danger btn-sm"
                    data-bs-toggle="modal" data-bs-target="#modalExcluirArena">
                <i class="bi bi-trash me-1"></i> Excluir
            </button>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="dashboard-title mb-1">
                <span style="font-size: 0.9em;">Empresa {{ $arena->owner?->company_name }}</span>
                <span class="text-muted mx-1" style="font-size: 0.65em; vertical-align: 0.08em;">&gt;</span>
                <span class="fs-3 text-danger">{{ $arena->name }}</span>
            </h1>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge {{ $arena->active ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $arena->active ? 'Arena ativa' : 'Arena desativada' }}
                </span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <button type="button"
                    class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal"
                    data-bs-target="#modalInformacoesArena">
                <div>
                    <h5 class="fw-bold text-dark" style="opacity: 1;">Informações da arena</h5>
                    <small class="text-muted">Dados e horários</small>
                </div>
                <i class="bi bi-building dashboard-icon text-primary"></i>
            </button>
        </div>

        <div class="col-6 col-lg-4">
            <button type="button"
                    class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal"
                    data-bs-target="#modalFuncionariosArena">
                <div>
                    <h5 class="fw-bold text-dark" style="opacity: 1;">Funcionários</h5>
                    <h2>{{ $arena->employees_count }}</h2>
                    <small class="text-muted">{{ $funcionariosAtivos }} ativos</small>
                </div>
                <i class="bi bi-person-badge dashboard-icon text-secondary"></i>
            </button>
        </div>

        <div class="col-6 col-lg-4">
            <button type="button"
                    class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal"
                    data-bs-target="#modalQuadrasArena">
                <div>
                    <h5 class="fw-bold text-dark" style="opacity: 1;">Quadras</h5>
                    <h2>{{ $arena->courts_count }}</h2>
                    <small class="text-muted">{{ $quadrasAtivas }} ativas</small>
                </div>
                <i class="bi bi-grid-3x3-gap dashboard-icon text-primary"></i>
            </button>
        </div>

        <div class="col-6 col-lg-4">
            <button type="button"
                    class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal"
                    data-bs-target="#modalReservasArena">
                <div>
                    <h5 class="fw-bold text-dark" style="opacity: 1;">Reservas da arena</h5>
                    <h2>{{ $reservasMes }}</h2>
                    <small class="text-muted">{{ $reservasTotal }} no histórico</small>
                </div>
                <i class="bi bi-calendar-check dashboard-icon text-warning"></i>
            </button>
        </div>

        <div class="col-6 col-lg-4">
            <button type="button"
                    class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal"
                    data-bs-target="#modalFaturamentoArena">
                <div>
                    <h5 class="fw-bold text-dark" style="opacity: 1;">Faturamento da arena</h5>
                    <h2 class="fs-4">
                        R$ {{ number_format($faturamentoMesAtual, 2, ',', '.') }}
                    </h2>
                    <small class="text-muted">Pagamentos confirmados</small>
                </div>
                <i class="bi bi-cash-coin dashboard-icon text-success"></i>
            </button>
        </div>

        <div class="col-6 col-lg-4">
            <button type="button"
                    class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal"
                    data-bs-target="#modalClientesArena">
                <div>
                    <h5 class="fw-bold text-dark" style="opacity: 1;">Clientes</h5>
                    <h2>{{ $clientesArena->total() }}</h2>
                    <small class="text-muted">Com reservas na arena</small>
                </div>
                <i class="bi bi-people dashboard-icon text-primary"></i>
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFaturamentoArena" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Faturamento da arena</h5>
                    <small class="text-muted">{{ $arena->name }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <span class="text-muted">Faturamento no mês</span>
                            <div class="fs-3 fw-bold text-success">
                                R$ {{ number_format($faturamentoMesAtual, 2, ',', '.') }}
                            </div>
                            <small class="text-muted">Pagamentos confirmados no mês atual</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <span class="text-muted">Faturamento acumulado</span>
                            <div class="fs-3 fw-bold text-success">
                                R$ {{ number_format($faturamentoTotal, 2, ',', '.') }}
                            </div>
                            <small class="text-muted">Desde o início dos registros no sistema</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="fw-bold mb-0">Faturamento por mês</h6>
                    <form method="GET" action="{{ route('admin.arenas.show', $arena) }}">
                        <input type="hidden" name="faturamento_modal" value="1">
                        <select name="ano_faturamento"
                                class="form-select form-select-sm"
                                aria-label="Selecionar ano"
                                onchange="this.form.submit()">
                            @foreach ($anosFaturamento as $ano)
                                <option value="{{ $ano }}" @selected($anoFaturamento === $ano)>
                                    {{ $ano }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Mês</th>
                                <th class="text-end pe-3">Faturamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($faturamentoMensal as $registro)
                                <tr>
                                    <td class="ps-3">
                                        {{ \Carbon\Carbon::createFromFormat('Y-m', $registro->mes)->translatedFormat('F/Y') }}
                                    </td>
                                    <td class="text-end fw-bold text-success pe-3">
                                        R$ {{ number_format($registro->total, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">
                                        Nenhum pagamento confirmado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalClientesArena" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Clientes da arena</h5>
                    <small class="text-muted">{{ $arena->name }}</small>
                </div>
                <div class="d-flex align-items-center justify-content-end gap-2 ms-auto">
                    <div class="collapse collapse-horizontal {{ request()->filled('busca_cliente') ? 'show' : '' }}"
                         id="buscaClientesArena">
                        <form method="GET"
                              action="{{ route('admin.arenas.show', $arena) }}"
                              data-client-search
                              data-endpoint="{{ route('admin.arenas.clients', $arena) }}"
                              data-target="clientesArenaBody">
                            <input type="hidden" name="clientes_modal" value="1">
                            <input type="search"
                                   name="busca_cliente"
                                   value="{{ request('busca_cliente') }}"
                                   class="form-control form-control-sm"
                                   style="width: min(300px, 48vw);"
                                   placeholder="Nome, e-mail ou telefone"
                                   aria-label="Buscar cliente">
                        </form>
                    </div>
                    <button type="button"
                            class="btn btn-link text-dark p-1"
                            data-bs-toggle="collapse"
                            data-bs-target="#buscaClientesArena"
                            aria-label="Abrir pesquisa"
                            aria-expanded="{{ request()->filled('busca_cliente') ? 'true' : 'false' }}">
                        <i class="bi bi-search fs-5"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="table-responsive border rounded" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0 small"
                           style="table-layout: fixed; width: 100%;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Cliente</th>
                                <th>E-mail</th>
                                <th>Telefone</th>
                                <th>Nascimento</th>
                                <th>Cadastro</th>
                                <th class="text-end">Reservas</th>
                                <th class="text-end pe-3">Total gasto</th>
                            </tr>
                        </thead>
                        <tbody id="clientesArenaBody">
                            @forelse ($clientesArena as $cliente)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $cliente->user?->name ?? '—' }}</td>
                                    <td class="text-break">{{ $cliente->user?->email ?? '—' }}</td>
                                    <td class="text-break">{{ $cliente->user?->phone ?: '—' }}</td>
                                    <td>
                                        {{ $cliente->date_of_birth
                                            ? \Carbon\Carbon::parse($cliente->date_of_birth)->format('d/m/Y')
                                            : '—' }}
                                    </td>
                                    <td>
                                        {{ $cliente->user?->created_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="text-end fw-bold">{{ $cliente->reservas_na_arena }}</td>
                                    <td class="text-end pe-3 fw-bold text-success">
                                        R$ {{ number_format($cliente->valor_total_na_arena, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        Nenhum cliente reservou nesta arena.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="text-center py-3 {{ $clientesArena->hasMorePages() ? '' : 'd-none' }}"
                         data-infinite-clients
                         data-target="clientesArenaBody"
                         data-next-url="{{ $clientesArena->hasMorePages()
                             ? route('admin.arenas.clients', [
                                 $arena,
                                 'page' => 2,
                                 'busca_cliente' => request('busca_cliente'),
                             ])
                             : '' }}">
                        <span class="spinner-border spinner-border-sm text-primary d-none"
                              data-client-spinner></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInformacoesArena" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Informações da arena</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><span class="text-muted">Empresa</span><br><strong>{{ $arena->owner?->company_name ?? '—' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Proprietário</span><br><strong>{{ $arena->owner?->user?->name ?? '—' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Descrição</span><br><strong>{{ $arena->description ?: '—' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Endereço</span><br><strong>{{ $arena->address_rua }}, {{ $arena->address_numero }} — {{ $arena->address_bairro }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Telefone</span><br><strong>{{ $arena->phone ?: '—' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">E-mail</span><br><strong>{{ $arena->contact_email ?: '—' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Criada em</span><br><strong>{{ optional($arena->created_at)->format('d/m/Y') ?? '—' }}</strong></div>
                    <div class="col-md-6">
                        <span class="text-muted">Formas de pagamento</span><br>
                        @forelse ($arena->paymentMethods as $metodo)
                            <span class="badge bg-secondary">{{ $metodo->label }}</span>
                        @empty
                            <strong>—</strong>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted">Política de Privacidade e Termos</span><br>
                        @if ($arena->owner?->user?->terms_accepted_at)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle me-1"></i> Aceitos
                            </span>
                            <div class="small text-muted mt-1">
                                {{ $arena->owner->user->terms_accepted_at->format('d/m/Y H:i') }}
                            </div>
                        @else
                            <span class="badge bg-warning text-dark">Aceite não registrado</span>
                        @endif
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold mb-3">Dias e horários</h6>
                @php
                    $dias = [
                        0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira',
                        3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado',
                    ];
                @endphp
                <div class="row g-2">
                    @foreach ($dias as $numero => $nome)
                        @php $periodos = $arena->businessHours->where('day_of_week', $numero); @endphp
                        <div class="col-md-6">
                            <div class="border rounded p-2 d-flex justify-content-between gap-3 h-100">
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
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $abaAtivaReservas = request('aba_reservas', 'mes');
    $statusReservas = [
        'pending' => ['Pendente', 'bg-warning text-dark'],
        'confirmed' => ['Confirmada', 'bg-success'],
        'cancelled' => ['Cancelada', 'bg-danger'],
        'completed' => ['Realizada', 'bg-primary'],
    ];
@endphp

<div class="modal fade" id="modalReservasArena" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Reservas da arena</h5>
                    <small class="text-muted">{{ $arena->name }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body overflow-y-auto" style="max-height: 72vh;">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-dark {{ $abaAtivaReservas === 'mes' ? 'active' : '' }}"
                                style="color: #212529 !important;"
                                data-bs-toggle="tab" data-bs-target="#reservas-arena-mes"
                                type="button" role="tab">
                            Reservas do mês
                            <span class="badge bg-primary ms-1">{{ $reservasMesLista->total() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-dark {{ $abaAtivaReservas === 'canceladas' ? 'active' : '' }}"
                                style="color: #212529 !important;"
                                data-bs-toggle="tab" data-bs-target="#reservas-arena-canceladas"
                                type="button" role="tab">
                            Canceladas
                            <span class="badge bg-danger ms-1">{{ $reservasCanceladasLista->total() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-dark {{ $abaAtivaReservas === 'historico' ? 'active' : '' }}"
                                style="color: #212529 !important;"
                                data-bs-toggle="tab" data-bs-target="#reservas-arena-historico"
                                type="button" role="tab">
                            Histórico
                            <span class="badge bg-secondary ms-1">{{ $historicoReservasLista->total() }}</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade {{ $abaAtivaReservas === 'mes' ? 'show active' : '' }}"
                         id="reservas-arena-mes" role="tabpanel">
                        @include('admin.arenas._booking-list', ['listaReservas' => $reservasMesLista])
                    </div>
                    <div class="tab-pane fade {{ $abaAtivaReservas === 'canceladas' ? 'show active' : '' }}"
                         id="reservas-arena-canceladas" role="tabpanel">
                        @include('admin.arenas._booking-list', ['listaReservas' => $reservasCanceladasLista])
                    </div>
                    <div class="tab-pane fade {{ $abaAtivaReservas === 'historico' ? 'show active' : '' }}"
                         id="reservas-arena-historico" role="tabpanel">
                        @include('admin.arenas._booking-list', ['listaReservas' => $historicoReservasLista])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalQuadrasArena" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Quadras da arena</h5>
                    <small class="text-muted">{{ $arena->name }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-3">
                    @forelse ($arena->courts->sortBy('name') as $quadra)
                        <div class="border rounded p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <h6 class="fw-bold mb-0">{{ $quadra->name }}</h6>
                                <span class="badge {{ $quadra->active ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $quadra->active ? 'Ativa' : 'Desativada' }}
                                </span>
                            </div>

                            @if ($quadra->description)
                                <div class="mb-2">
                                    <span class="small fw-bold">Descrição</span><br>
                                    <span class="small text-muted">{{ $quadra->description }}</span>
                                </div>
                            @endif

                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <span class="small text-muted">Código</span><br>
                                    <strong>#{{ $quadra->id }}</strong>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="small text-muted">Arena</span><br>
                                    <strong>{{ $arena->name }}</strong>
                                </div>
                                @if ($quadra->created_at)
                                    <div class="col-6 col-md-3">
                                        <span class="small text-muted">Cadastrada em</span><br>
                                        <strong>{{ $quadra->created_at->format('d/m/Y H:i') }}</strong>
                                    </div>
                                @endif
                                @if ($quadra->updated_at)
                                    <div class="col-6 col-md-3">
                                        <span class="small text-muted">Última atualização</span><br>
                                        <strong>{{ $quadra->updated_at->format('d/m/Y H:i') }}</strong>
                                    </div>
                                @endif
                                @if ($quadra->sports->isNotEmpty())
                                    <div class="col-md-8">
                                        <span class="small fw-bold">Esportes</span><br>
                                        <span class="small">
                                            {{ $quadra->sports
                                                ->map(fn ($sport) => \App\Models\Court::SPORTS[$sport->sport] ?? $sport->sport)
                                                ->implode(', ') }}
                                        </span>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    <span class="small text-muted">Valor por hora</span><br>
                                    <strong class="text-success">
                                        R$ {{ number_format($quadra->hourly_rate, 2, ',', '.') }}
                                    </strong>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap justify-content-between gap-2 mt-3">
                                @if ($quadra->active)
                                    <button type="button" class="btn btn-warning btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDesativarQuadra{{ $quadra->id }}">
                                        <i class="bi bi-power me-1"></i> Desativar
                                    </button>
                                @else
                                    <form method="POST"
                                          action="{{ route('admin.arenas.courts.activate', [$arena, $quadra]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bi bi-check-circle me-1"></i> Ativar
                                        </button>
                                    </form>
                                @endif
                                <button type="button" class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalExcluirQuadra{{ $quadra->id }}">
                                    <i class="bi bi-trash me-1"></i> Excluir
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            Nenhuma quadra cadastrada nesta arena.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@foreach ($arena->courts as $quadra)
    <div class="modal fade" id="modalDesativarQuadra{{ $quadra->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.arenas.courts.deactivate', [$arena, $quadra]) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Desativar quadra</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Deseja realmente desativar <strong>{{ $quadra->name }}</strong>?
                        As reservas pendentes ou confirmadas desta quadra serão canceladas.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Sim, desativar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalExcluirQuadra{{ $quadra->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.arenas.courts.destroy', [$arena, $quadra]) }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Excluir quadra</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            Deseja realmente excluir <strong>{{ $quadra->name }}</strong>?
                        </div>
                        As reservas ativas serão canceladas e o histórico será preservado.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Sim, excluir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="modalFuncionariosArena" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Funcionários da arena</h5>
                    <small class="text-muted">{{ $arena->name }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-3">
                    @forelse ($arena->employees->sortBy(fn ($employee) => $employee->user?->name) as $employee)
                        <div class="border rounded p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div>
                                    <h6 class="fw-bold mb-1">{{ $employee->user?->name ?? 'Funcionário sem usuário' }}</h6>
                                    <span class="badge {{ $employee->active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $employee->active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalExcluirFuncionario{{ $employee->id }}">
                                    <i class="bi bi-trash me-1"></i> Excluir
                                </button>
                            </div>

                            <div class="row g-3 mt-1">
                                @if ($employee->user?->email)
                                    <div class="col-md-6">
                                        <span class="small text-muted">E-mail</span><br>
                                        <strong>{{ $employee->user->email }}</strong>
                                    </div>
                                @endif
                                @if ($employee->user?->phone)
                                    <div class="col-md-6">
                                        <span class="small text-muted">Telefone</span><br>
                                        <strong>{{ $employee->user->phone }}</strong>
                                    </div>
                                @endif
                                @if ($employee->position)
                                    <div class="col-md-6">
                                        <span class="small text-muted">Cargo</span><br>
                                        <strong>{{ $employee->position }}</strong>
                                    </div>
                                @endif
                                @if ($employee->access_level)
                                    <div class="col-md-6">
                                        <span class="small text-muted">Nível de acesso</span><br>
                                        <strong>{{ $employee->access_level === 'managerial' ? 'Gerencial' : 'Básico' }}</strong>
                                    </div>
                                @endif
                                @if ($employee->created_at)
                                    <div class="col-md-6">
                                        <span class="small text-muted">Cadastrado em</span><br>
                                        <strong>{{ $employee->created_at->format('d/m/Y H:i') }}</strong>
                                    </div>
                                @endif
                                @if ($employee->createdBy?->name)
                                    <div class="col-md-6">
                                        <span class="small text-muted">Cadastrado por</span><br>
                                        <strong>{{ $employee->createdBy->name }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">Nenhum funcionário cadastrado nesta arena.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@foreach ($arena->employees as $employee)
    <div class="modal fade" id="modalExcluirFuncionario{{ $employee->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.arenas.employees.destroy', [$arena, $employee]) }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Excluir funcionário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Deseja realmente excluir
                        <strong>{{ $employee->user?->name ?? 'este funcionário' }}</strong>
                        da Arena {{ $arena->name }}?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Sim, excluir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="modalDesativarArena" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.arenas.deactivate', $arena) }}">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Desativar arena</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Deseja realmente desativar <strong>{{ $arena->name }}</strong>?
                    As quadras serão desativadas e as reservas pendentes ou confirmadas serão canceladas.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Sim, desativar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExcluirArena" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.arenas.destroy', $arena) }}">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Excluir arena</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        Deseja realmente excluir <strong>{{ $arena->name }}</strong>?
                    </div>
                    A arena deixará de aparecer no sistema, as reservas ativas serão canceladas
                    e o histórico financeiro será preservado.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Sim, excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if (request()->boolean('reservas_modal'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(
                document.getElementById('modalReservasArena')
            ).show();
        });
    </script>
@endif

@include('admin.clients._infinite-script')

@if (request()->boolean('clientes_modal'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(
                document.getElementById('modalClientesArena')
            ).show();
        });
    </script>
@endif

@if (request()->boolean('quadras_modal'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(
                document.getElementById('modalQuadrasArena')
            ).show();
        });
    </script>
@endif

@if (request()->boolean('faturamento_modal'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(
                document.getElementById('modalFaturamentoArena')
            ).show();
        });
    </script>
@endif

@endsection

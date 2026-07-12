@extends('layouts.main')

@section('title', 'Owner Dashboard')

@section('content')

<div class="container py-4 painel">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="fw-bold">
                Bem-vindo, {{ auth()->user()->name }}!
                <span class="badge bg-primary align-middle fs-6 fw-semibold">
                    {{ $ehGerente ? 'Painel do gerente' : 'Painel do proprietário' }}
                </span>
            </h1>

            <p class="text-muted fs-4 mb-0">
                @if ($ehGerente)
                    Gerencie sua arena, quadras, reservas e funcionários
                @else
                    Gerencie suas arenas, quadras, reservas e funcionários
                @endif
            </p>
        </div>

        @unless ($ehGerente)
            <a href="{{ route('owners.arena.choose') }}" class="text-decoration-none text-reset">
                <div class="card shadow-sm border-0 text-center card-hover">
                    <div class="card-body">
                        <h6 class="text-secondary mb-1">Total de Arenas</h6>
                        <h2 class="fw-bold mb-1">{{ $arenasCount }}</h2>
                        <div class="small">
                            <span class="text-success">{{ $arenasActive }} ativas</span>
                            · <span class="text-muted">{{ $arenasCount - $arenasActive }} inativas</span>
                        </div>
                    </div>
                </div>
            </a>
        @endunless
    </div>

    @if ($owner && ! $owner->active)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <strong>Sua empresa está desativada.</strong>
                @if ($owner->deactivation_source === 'admin')
                    Ela foi desativada pelo administrador do sistema e está bloqueada para reativação.
                @else
                    Ela foi desativada por você e pode ser reativada.
                @endif
            </div>

            @if ($owner->deactivation_source === 'self')
                <form method="POST" action="{{ route('owners.company.activate') }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-success">Ativar empresa</button>
                </form>
            @endif
        </div>
    @endif

    @if ($errors->has('empresa'))
        <div class="alert alert-danger">{{ $errors->first('empresa') }}</div>
    @endif

    @if ($selectedArena)
        <div class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="fs-5">
                🏟 Gerenciando: <strong>{{ $selectedArena->name }}</strong>
            </div>

            @if ($arenasCount > 1)
                <form action="{{ route('owners.arena.select') }}" method="POST"
                      class="d-flex flex-wrap align-items-center gap-2 mb-0">
                    @csrf
                    <label class="mb-0 small text-nowrap">Trocar de arena:</label>
                    {{-- min-w-0: sem isso o <select> não encolhe abaixo do nome de
                         arena mais longo e estoura a linha em telas estreitas. --}}
                    <select name="arena_id" class="form-select form-select-sm min-w-0"
                            onchange="this.form.submit()">
                        @foreach ($arenas as $a)
                            <option value="{{ $a->id }}"
                                {{ $a->id === $selectedArena->id ? 'selected' : '' }}>
                                {{ $a->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    @endif

    @if ($selectedArena && ! $selectedArena->active)
        <div class="alert alert-warning">
            ⚠️ Esta arena está <strong>inativa</strong>. Reative-a para abrir o caixa,
            cadastrar quadras ou funcionários.
        </div>
    @endif

    <!-- Cards -->
    <div class="row g-4 mb-4">

        <div class="col-md-3">
            @if ($selectedArena)
                <a href="{{ route('arenas.show', $selectedArena->id) }}"
                   class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="text-secondary">Arena Atual</h4>
                                <span class="text-muted small">Visualizar</span>
                            </div>
                            <h3 class="fw-bold mb-1">{{ $selectedArena->name }}</h3>
                            @if ($selectedArena->active)
                                <span class="badge bg-success">Ativa</span>
                            @else
                                <span class="badge bg-secondary">Inativa</span>
                            @endif
                        </div>
                    </div>
                </a>
            @else
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h4 class="text-secondary">Arena Atual</h4>
                        <h3 class="fw-bold">—</h3>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-3">
            @if ($selectedArena)
                <a href="{{ route('quadras.index') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="text-secondary">Quadras</h4>
                                <span class="text-muted small">Visualizar</span>
                            </div>
                            <h1 class="fw-bold mb-1">{{ $courtsCount }}</h1>
                            <div class="small">
                                <span class="text-success">{{ $courtsActive }} ativas</span>
                                · <span class="text-muted">{{ $courtsCount - $courtsActive }} inativas</span>
                            </div>
                        </div>
                    </div>
                </a>
            @else
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h4 class="text-secondary">Quadras</h4>
                        <h1 class="fw-bold">{{ $courtsCount }}</h1>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-3">
            @if ($selectedArena)
                <a href="{{ route('bookings.today') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="text-secondary">Reservas de Hoje</h4>
                                <span class="text-muted small">Visualizar</span>
                            </div>
                            <h1 class="fw-bold">{{ $agendamentosHoje }}</h1>
                        </div>
                    </div>
                </a>
            @else
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h4 class="text-secondary">Reservas de Hoje</h4>
                        <h1 class="fw-bold">{{ $agendamentosHoje }}</h1>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-3">
            @if ($selectedArena)
                <a href="{{ route('bookings.pending') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover {{ $pendentesCount > 0 ? 'border border-warning' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="text-secondary">Aguardando confirmação</h4>
                                <span class="text-muted small">Visualizar</span>
                            </div>
                            <h1 class="fw-bold mb-1">{{ $pendentesCount }}</h1>
                            @if ($pendentesCount > 0)
                                <span class="badge bg-warning text-dark">Requer ação</span>
                            @else
                                <div class="small text-muted">Tudo em dia</div>
                            @endif
                        </div>
                    </div>
                </a>
            @else
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h4 class="text-secondary">Aguardando confirmação</h4>
                        <h1 class="fw-bold">{{ $pendentesCount }}</h1>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            @if ($selectedArena)
                <a href="{{ route('clients.index') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="text-secondary">Clientes</h4>
                                <span class="text-muted small">Visualizar</span>
                            </div>
                            <h1 class="fw-bold">{{ $customersCount }}</h1>
                        </div>
                    </div>
                </a>
            @else
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h4 class="text-secondary">Clientes</h4>
                        <h1 class="fw-bold">{{ $customersCount }}</h1>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            @if ($selectedArena)
                <a href="{{ route('employees.index') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="text-secondary">Funcionários</h4>
                                <span class="text-muted small">Visualizar</span>
                            </div>
                            <h1 class="fw-bold mb-1">{{ $employeesCount }}</h1>
                            <div class="small">
                                <span class="text-success">{{ $employeesActive }} ativos</span>
                                · <span class="text-muted">{{ $employeesCount - $employeesActive }} inativos</span>
                            </div>
                        </div>
                    </div>
                </a>
            @else
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h4 class="text-secondary">Funcionários</h4>
                        <h1 class="fw-bold">{{ $employeesCount }}</h1>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            @if ($selectedArena)
                <a href="{{ route('caixa.report') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="text-secondary">Lucro do mês</h4>
                                <span class="text-muted small">Visualizar</span>
                            </div>
                            <h1 class="fw-bold {{ $lucroMes >= 0 ? 'text-success' : 'text-danger' }}">
                                R$ {{ number_format($lucroMes, 2, ',', '.') }}
                            </h1>
                            <div class="small text-muted">
                                {{ $mesAtualLabel }} ·
                                <span class="text-success">+{{ number_format($entradasMes, 2, ',', '.') }}</span>
                                <span class="text-danger">−{{ number_format($saidasMes, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @else
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h4 class="text-secondary">Lucro do mês</h4>
                        <h1 class="fw-bold text-success">R$ 0,00</h1>
                    </div>
                </div>
            @endif
        </div>

    </div>

    <!-- Actions + Bookings -->
    <div class="row">

        <div class="col-lg-6 mb-4">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h2 class="fw-bold mb-4">
                        Ações Rápidas
                    </h2>

                    @php $arenaInativa = $selectedArena && ! $selectedArena->active; @endphp

                    <div class="d-grid gap-3">

                        <a href="{{ route('caixa.index') }}"
                            class="btn btn-outline-dark btn-lg">
                            💰 Caixa
                        </a>

                        @unless ($arenaInativa)
                            <a href="{{ route('bookings.presencial.create') }}"
                                class="btn btn-outline-dark btn-lg">
                                📅 Registrar reserva
                            </a>
                        @endunless

                        <a href="{{ route('caixa.balance') }}"
                            class="btn btn-outline-dark btn-lg">
                            📊 Balanço financeiro
                        </a>

                        <a href="{{ route('employees.create') }}"
                            class="btn btn-outline-dark btn-lg {{ $arenaInativa ? 'disabled' : '' }}"
                            @if ($arenaInativa) aria-disabled="true" tabindex="-1" title="Arena inativa" @endif>
                            👤 Novo Funcionário
                        </a>

                        <a href="{{ route('quadras.create') }}"
                            class="btn btn-outline-dark btn-lg {{ $arenaInativa ? 'disabled' : '' }}"
                            @if ($arenaInativa) aria-disabled="true" tabindex="-1" title="Arena inativa" @endif>
                            ⚽ Nova Quadra
                        </a>

                        @unless ($ehGerente)
                            <a href="{{ route('arenas.create') }}"
                                class="btn btn-outline-dark btn-lg">
                                🏟 Nova Arena
                            </a>
                        @endunless

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-6 mb-4">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h2 class="fw-bold mb-4">
                        Proximos Agendamentos
                        <span class="badge bg-secondary fs-6 align-middle">
                            {{ $proximosCount }}
                        </span>
                    </h2>

                    @php
                        $statusInfo = [
                            'pending'   => ['Pendente',   'bg-warning text-dark'],
                            'confirmed' => ['Confirmada', 'bg-success'],
                            'completed' => ['Concluída',  'bg-success'],
                            'cancelled' => ['Cancelada',  'bg-danger'],
                        ];
                    @endphp

                    <div class="table-responsive">
                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Quadra</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($proximosAgendamentos as $booking)
                                <tr>
                                    <td>{{ $booking->nomeCliente() }}</td>
                                    <td>{{ $booking->court->name }}</td>
                                    <td>
                                        {{ $booking->date->format('d/m/Y') }}
                                        {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
                                    </td>
                                    <td>
                                        @if ($booking->estaEmAndamento())
                                            <span class="badge text-center" style="min-width: 100px; background:#021B35; color:#fff;">Em andamento</span>
                                        @else
                                            @php $st = $statusInfo[$booking->status] ?? [$booking->status, 'bg-secondary']; @endphp
                                            <span class="badge {{ $st[1] }} text-center" style="min-width: 100px;">{{ $st[0] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('bookings.show', $booking) }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-info-circle me-1"></i> Detalhes
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        Nenhum agendamento por enquanto
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>
                    </div>

                    <div class="text-center d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('bookings.index') }}" class="btn btn-outline-dark btn-sm">
                            @if ($proximosCount > 4)
                                Ver todos ({{ $proximosCount }})
                            @else
                                Gerenciar agendamentos
                            @endif
                        </a>
                        <a href="{{ route('bookings.history') }}" class="btn btn-outline-dark btn-sm">
                            <i class="bi bi-clock-history me-1"></i> Histórico
                        </a>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

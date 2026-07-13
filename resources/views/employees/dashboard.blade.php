@extends('layouts.main')

@section('title', 'Painel do Atendente')

@section('content')

<div class="container py-4 painel">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="fw-bold">
                Bem-vindo, {{ auth()->user()->name }}!
                <span class="badge bg-primary align-middle fs-6 fw-semibold">Painel do atendente</span>
            </h1>
            <p class="text-muted fs-4 mb-0">
                Atenda reservas e o caixa
                @if ($arena)
                    — <span class="fw-semibold">{{ $arena->name }}</span>
                @endif
            </p>
        </div>
    </div>

    @if (! $arena)
        <div class="alert alert-warning shadow-sm">
            Sua conta de funcionário ainda não está vinculada a nenhuma arena.
            Peça ao proprietário para concluir o vínculo.
        </div>
    @else

        @if (! $arena->active)
            <div class="alert alert-warning">
                ⚠️ Esta arena está <strong>inativa</strong>. Algumas ações ficam indisponíveis
                até o proprietário reativá-la.
            </div>
        @endif

        <!-- Cards -->
        <div class="row g-4 mb-4">

            {{-- Arena (consulta) --}}
            <div class="col-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100 card-hover" role="button"
                     data-bs-toggle="modal" data-bs-target="#modalArenaInfo">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h4 class="text-secondary">Arena Atual</h4>
                            <span class="text-muted small">Ver</span>
                        </div>
                        <h3 class="fw-bold mb-1">{{ $arena->name }}</h3>
                        @if ($arena->active)
                            <span class="badge bg-success">Ativa</span>
                        @else
                            <span class="badge bg-secondary">Inativa</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Quadras (consulta) --}}
            <div class="col-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100 card-hover" role="button"
                     data-bs-toggle="modal" data-bs-target="#modalQuadrasAtendente">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h4 class="text-secondary">Quadras</h4>
                            <span class="text-muted small">Ver</span>
                        </div>
                        <h1 class="fw-bold mb-1">{{ $courtsCount }}</h1>
                        <div class="small">
                            <span class="text-success">{{ $courtsActive }} ativas</span>
                            · <span class="text-muted">{{ $courtsCount - $courtsActive }} inativas</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Clientes (consulta) --}}
            <div class="col-6 col-lg-4">
                <a href="{{ route('clients.index') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="text-secondary">Clientes</h4>
                                <span class="text-muted small">Ver</span>
                            </div>
                            <h1 class="fw-bold">{{ $customersCount }}</h1>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Reservas de hoje --}}
            <div class="col-6 col-lg-4">
                <a href="{{ route('bookings.today') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="text-secondary">Reservas de Hoje</h4>
                                <span class="text-muted small">Ver</span>
                            </div>
                            <h1 class="fw-bold">{{ $agendamentosHoje }}</h1>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Aguardando confirmação --}}
            <div class="col-6 col-lg-4">
                <a href="{{ route('bookings.pending') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover {{ $pendentesCount > 0 ? 'border border-warning' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="text-secondary">Aguardando confirmação</h4>
                                <span class="text-muted small">Ver</span>
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
            </div>

        </div>

        <!-- Ações + Próximos agendamentos -->
        <div class="row">

            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h2 class="fw-bold mb-4">Ações Rápidas</h2>

                        @php $arenaInativa = ! $arena->active; @endphp

                        <div class="d-grid gap-3">
                            <a href="{{ route('caixa.index') }}" class="btn btn-outline-dark btn-lg">
                                💰 Caixa
                            </a>

                            <a href="{{ route('bookings.presencial.create') }}"
                               class="btn btn-outline-dark btn-lg {{ $arenaInativa ? 'disabled' : '' }}"
                               @if ($arenaInativa) aria-disabled="true" tabindex="-1" title="Arena inativa" @endif>
                                📅 Registrar reserva
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h2 class="fw-bold mb-4">
                            Próximos Agendamentos
                            <span class="badge bg-secondary fs-6 align-middle">{{ $proximosCount }}</span>
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
                                            <td>{{ $booking->court->name ?? '—' }}</td>
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
                                                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-info-circle me-1"></i> Detalhes
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Nenhum agendamento por enquanto</td>
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

    @endif

</div>

@if ($arena)
    {{-- Modal: dados da arena (somente leitura) --}}
    <div class="modal fade" id="modalArenaInfo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Dados da arena</h5>
                        <small class="text-muted">{{ $arena->name }}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <span class="small text-dark fw-bold">Situação</span><br>
                            <span class="badge {{ $arena->active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $arena->active ? 'Ativa' : 'Inativa' }}
                            </span>
                        </div>
                        @if ($arena->description)
                            <div class="col-12">
                                <span class="small text-dark fw-bold">Descrição</span><br>
                                <span>{{ $arena->description }}</span>
                            </div>
                        @endif
                        <div class="col-12">
                            <span class="small text-dark fw-bold">Endereço</span><br>
                            <span>
                                {{ $arena->address_rua ?: '—' }}{{ $arena->address_numero ? ', '.$arena->address_numero : '' }}
                                {{ $arena->address_bairro ? ' — '.$arena->address_bairro : '' }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="small text-dark fw-bold">Telefone</span><br>
                            <span>{{ $arena->phone ?: '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="small text-dark fw-bold">E-mail</span><br>
                            <span class="text-break">{{ $arena->contact_email ?: '—' }}</span>
                        </div>
                        <div class="col-12">
                            <span class="small text-dark fw-bold">Taxa de cancelamento</span><br>
                            <span>{{ $arena->charges_cancellation_fee ? 'A arena cobra taxa de cancelamento' : 'A arena não cobra taxa de cancelamento' }}</span>
                        </div>
                        <div class="col-12">
                            <span class="small text-dark fw-bold">Quadras</span><br>
                            <span>{{ $courtsCount }} no total · {{ $courtsActive }} ativas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: quadras da arena (somente leitura) --}}
    <div class="modal fade" id="modalQuadrasAtendente" tabindex="-1" aria-hidden="true">
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
                    <div class="row g-3 align-items-start">
                        @forelse ($courts as $quadra)
                            <div class="col-12 col-sm-6">
                            <div class="border rounded p-3 h-100 d-flex flex-column" data-atendente-court-card>
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <h6 class="fw-bold mb-0">{{ $quadra->name }}</h6>
                                    <span class="badge {{ $quadra->active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $quadra->active ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </div>

                                <div class="collapse my-3 w-100" id="detalhesQuadraAtendente{{ $quadra->id }}">
                                    <div class="border-top pt-3">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <span class="small text-dark fw-bold">Valor por hora</span><br>
                                                <span class="text-success">R$ {{ number_format($quadra->hourly_rate, 2, ',', '.') }}</span>
                                            </div>
                                            <div class="col-12">
                                                <span class="small text-dark fw-bold">Esportes</span><br>
                                                <span>{{ $quadra->sports->isNotEmpty()
                                                    ? $quadra->sports->map(fn ($s) => \App\Models\Court::SPORTS[$s->sport] ?? $s->sport)->implode(', ')
                                                    : '—' }}</span>
                                            </div>
                                            @if ($quadra->created_at)
                                                <div class="col-6"><span class="small text-dark fw-bold">Cadastrada em</span><br><span>{{ $quadra->created_at->format('d/m/Y H:i') }}</span></div>
                                            @endif
                                            @if ($quadra->updated_at)
                                                <div class="col-6"><span class="small text-dark fw-bold">Última atualização</span><br><span>{{ $quadra->updated_at->format('d/m/Y H:i') }}</span></div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-primary btn-sm w-100 mt-auto atendente-court-toggle"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#detalhesQuadraAtendente{{ $quadra->id }}"
                                        aria-controls="detalhesQuadraAtendente{{ $quadra->id }}"
                                        aria-expanded="false">
                                    Ver detalhes
                                </button>
                            </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted py-4">Nenhuma quadra cadastrada nesta arena.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let activeCourtDetails = null;

            document.querySelectorAll('#modalQuadrasAtendente .collapse').forEach(function (details) {
                const button = document.querySelector(
                    `.atendente-court-toggle[data-bs-target="#${details.id}"]`
                );

                if (!button) {
                    return;
                }

                details.addEventListener('shown.bs.collapse', function () {
                    if (activeCourtDetails && activeCourtDetails !== details) {
                        bootstrap.Collapse.getOrCreateInstance(activeCourtDetails, { toggle: false }).hide();
                    }
                    activeCourtDetails = details;
                    button.textContent = 'Fechar detalhes';
                });

                details.addEventListener('hidden.bs.collapse', function () {
                    button.textContent = 'Ver detalhes';
                    if (activeCourtDetails === details) {
                        activeCourtDetails = null;
                    }
                });
            });

            document.addEventListener('click', function (event) {
                if (!activeCourtDetails) {
                    return;
                }
                const activeCard = activeCourtDetails.closest('[data-atendente-court-card]');
                if (activeCard && !activeCard.contains(event.target)) {
                    bootstrap.Collapse.getOrCreateInstance(activeCourtDetails, { toggle: false }).hide();
                }
            });
        });
    </script>
@endif

@endsection

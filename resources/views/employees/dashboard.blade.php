@extends('layouts.main')

@section('title', 'Painel do Funcionário')

@section('content')

<div class="container py-4">

    <div class="mb-4">
        <h1 class="fw-bold mb-1">Painel do Funcionário</h1>
        <p class="text-muted fs-5 mb-0">
            Gerencie agendamentos e atendimento ao cliente
            @if ($arena)
                — <span class="fw-semibold">{{ $arena->name }}</span>
            @endif
        </p>
    </div>

    @if (! $arena)
        <div class="alert alert-warning shadow-sm">
            Sua conta de funcionário ainda não está vinculada a nenhuma arena.
            Peça ao proprietário para concluir o vínculo.
        </div>
    @else

        <!-- Cards -->
        <div class="row g-4 mb-4">

            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-secondary mb-1">Agendamentos Hoje</h5>
                            <h1 class="fw-bold mb-0">{{ $hoje->count() }}</h1>
                        </div>
                        <div class="fs-1 text-secondary"><i class="bi bi-calendar-check"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-secondary mb-1">Pendentes</h5>
                            <h1 class="fw-bold mb-0">{{ $pendentes }}</h1>
                        </div>
                        <div class="fs-1 text-warning"><i class="bi bi-hourglass-split"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-secondary mb-1">Confirmados na semana</h5>
                            <h1 class="fw-bold mb-0">{{ $semana->count() }}</h1>
                        </div>
                        <div class="fs-1 text-success"><i class="bi bi-check2-circle"></i></div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-0" id="agendamentoTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#hoje" type="button">
                    HOJE ({{ $hoje->count() }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#semana" type="button">
                    ESTA SEMANA ({{ $semana->count() }})
                </button>
            </li>
        </ul>

        <div class="card shadow-sm border-0 rounded-top-0">
            <div class="tab-content">

                {{-- HOJE --}}
                <div class="tab-pane fade show active" id="hoje">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Horário</th>
                                    <th>Cliente</th>
                                    <th>Quadra</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th class="pe-3">Pagamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($hoje as $booking)
                                    <tr>
                                        <td class="ps-3">{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</td>
                                        <td>{{ $booking->client->user->name ?? '—' }}</td>
                                        <td>{{ $booking->court->name ?? '—' }}</td>
                                        <td>R$ {{ number_format($booking->total_amount, 2, ',', '.') }}</td>
                                        <td>
                                            @if ($booking->estaEmAndamento())
                                                <span class="badge" style="background:#021B35; color:#fff;">Em andamento</span>
                                            @else
                                                <span class="badge bg-success">Confirmada</span>
                                            @endif
                                        </td>
                                        <td class="pe-3">@include('partials.payment-badge', ['booking' => $booking])</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Nenhuma reserva confirmada para hoje.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ESTA SEMANA --}}
                <div class="tab-pane fade" id="semana">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Data</th>
                                    <th>Horário</th>
                                    <th>Cliente</th>
                                    <th>Quadra</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th class="pe-3">Pagamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($semana as $booking)
                                    <tr>
                                        <td class="ps-3">{{ $booking->date->format('d/m') }}</td>
                                        <td>{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</td>
                                        <td>{{ $booking->client->user->name ?? '—' }}</td>
                                        <td>{{ $booking->court->name ?? '—' }}</td>
                                        <td>R$ {{ number_format($booking->total_amount, 2, ',', '.') }}</td>
                                        <td>
                                            @if ($booking->estaEmAndamento())
                                                <span class="badge" style="background:#021B35; color:#fff;">Em andamento</span>
                                            @else
                                                <span class="badge bg-success">Confirmada</span>
                                            @endif
                                        </td>
                                        <td class="pe-3">@include('partials.payment-badge', ['booking' => $booking])</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Nenhuma reserva confirmada nesta semana.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    @endif

</div>

@endsection

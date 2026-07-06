@extends('layouts.main')

@section('title', 'Dashboard')

@section('content')

<div class="dashboard-container container-fluid py-4">

    <!-- Cabeçalho -->
    <div class="mb-5">
        <h1 class="dashboard-title">
            Bem-vindo, {{ Auth::user()->name }}!
        </h1>

        <p class="dashboard-subtitle">
            Gerencie seus agendamentos e reserve novas quadras
        </p>
    </div>

    <!-- Cards Resumo -->
    <div class="row g-4 mb-4">

        <div class="col-md-4">
            <a href="{{ route('client.bookings.today') }}"
               class="dashboard-card text-decoration-none text-body"
               aria-label="Ver agendamentos de hoje">

                <div>
                    <h5>Agendamentos hoje</h5>
                    <h2>{{ $hojeCount ?? 0 }}</h2>
                </div>

                <i class="bi bi-calendar-event dashboard-icon text-primary"></i>

            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ route('client.bookings.pending') }}"
               class="dashboard-card text-decoration-none text-body"
               aria-label="Ver agendamentos pendentes">

                <div>
                    <h5>Agendamentos pendentes</h5>
                    <h2>{{ $pendentes ?? 0 }}</h2>
                </div>

                <i class="bi bi-three-dots dashboard-icon text-warning"></i>

            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ route('client.bookings.index') }}"
               class="dashboard-card text-decoration-none text-body"
               aria-label="Ver próximos agendamentos">

                <div>
                    <h5>Agendamentos confirmados</h5>
                    <h2>{{ $confirmados ?? 0 }}</h2>
                </div>

                <i class="bi bi-check-circle dashboard-icon text-success"></i>

            </a>
        </div>

    </div>

    <!-- Conteúdo Principal -->
    <div class="row g-4">

        <!-- Agendamentos -->
        <div class="col-lg-8">

            <div class="dashboard-box">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="section-title mb-0">
                        Próximos Agendamentos
                        <span class="badge bg-secondary fs-6 align-middle">{{ $proximosCount ?? 0 }}</span>
                    </h2>

                    <a href="{{ route('client.bookings.index') }}"
                       class="btn btn-link text-decoration-none fw-semibold p-0">
                        Ver todos
                    </a>
                </div>

                @php
                    $badges = [
                        'pending'   => ['Pendente', 'bg-warning text-dark'],
                        'confirmed' => ['Confirmada', 'bg-success'],
                    ];
                @endphp

                @if (($proximos ?? collect())->isEmpty())
                    <p class="text-muted">
                        Nenhum agendamento próximo
                    </p>
                @else
                    <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Arena</th>
                                <th>Quadra</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($proximos as $b)
                                <tr>
                                    <td>{{ $b->court->arena->name ?? '—' }}</td>
                                    <td>{{ $b->court->name ?? '—' }}</td>
                                    <td>
                                        {{ $b->date->format('d/m/Y') }}
                                        {{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}
                                    </td>
                                    <td>
                                        @php [$rotulo, $cor] = $badges[$b->status] ?? [$b->status, 'bg-secondary']; @endphp
                                        <span class="badge {{ $cor }}">{{ $rotulo }}</span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('bookings.show', $b) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-info-circle me-1"></i> Detalhes
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif

            </div>

        </div>

        <!-- Lateral -->
        <div class="col-lg-4">

            <!-- Ações rápidas -->
            <div class="dashboard-box mb-4">

                <h2 class="section-title">
                    Ações Rápidas
                </h2>

                <div class="d-grid gap-3 mt-4">

                    <a href="{{ route('client.arenas.index') }}" class="btn dashboard-btn-outline">
                        <i class="bi bi-calendar-plus me-2"></i>
                        NOVA RESERVA
                    </a>

                    <a href="{{ route('client.bookings.history') }}" class="btn dashboard-btn-outline">
                        <i class="bi bi-clock-history me-2"></i>
                        HISTÓRICO
                    </a>

                    <a href="{{ route('client.profile.edit') }}"
                       class="btn dashboard-btn-outline">
                        <i class="bi bi-person-fill me-2"></i>
                        MEU PERFIL
                    </a>

                </div>

            </div>

            @if (auth()->user()->type === 'owner')
                <!-- Proprietário -->
                <div class="arena-owner-card">

                    <h3>
                        <i class="bi bi-building-fill me-2"></i>
                        Área do Proprietário
                    </h3>

                    <p>
                        Você possui arenas cadastradas.
                        Gerencie quadras, reservas e funcionários.
                    </p>

                    <a href="{{ route('arenas.index') }}"
                       class="btn btn-warning w-100">

                        <i class="bi bi-gear-fill me-2"></i>
                        ACESSAR MINHAS ARENAS

                    </a>

                </div>

            @endif

        </div>

    </div>

    <section class="mt-5">
        <div class="border-top border-start border-end border-3 rounded-top-3 p-3 p-md-4 mb-4"
             style="border-color: #021b35 !important;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="section-title mb-1">Arenas disponíveis</h2>
                    <p class="text-muted mb-0">Conheça as arenas e encontre sua próxima quadra.</p>
                </div>

                <a href="{{ route('client.arenas.index') }}" class="btn btn-outline-primary">
                    Ver todas
                </a>
            </div>

            <div class="row mt-3">
                <div class="col-12 col-lg-6">
                    <form method="GET" action="{{ route('client.arenas.index') }}"
                          class="arena-search-form arena-search-box shadow-sm mb-0">
                        <div class="input-group">
                            <input type="search" name="busca" class="form-control border-end-0"
                                   placeholder="Pesquisar pelo nome da arena ou do proprietário"
                                   aria-label="Pesquisar arena">
                            <button type="submit" class="btn bg-white text-secondary border border-start-0"
                                    style="border-color: var(--bs-border-color) !important;"
                                    aria-label="Pesquisar" title="Pesquisar">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-4" data-arena-results>
            @forelse ($arenas as $arena)
                <div class="col-6 col-lg-3">
                    @include('client.arenas._gallery-card', [
                        'arenaUrl' => route('client.arenas.show', $arena),
                        'botaoTexto' => 'Ver arena',
                    ])
                </div>
            @empty
                <div class="col-12">
                    <div class="dashboard-box text-center text-muted">
                        Nenhuma arena disponível no momento.
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    @if(auth()->user()->type === 'client')
        <a href="{{ route('register.arena.owners') }}"
           class="arena-owner-card mt-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 text-decoration-none text-white">
            <div>
                <h3 class="text-white mb-2">
                    <i class="bi bi-trophy-fill me-2"></i>
                    Tem uma Arena?
                </h3>
                <p class="text-white mb-0">
                    Cadastre sua arena e gerencie quadras, agendamentos e funcionários como proprietário.
                </p>
            </div>

            <span class="btn btn-warning flex-shrink-0 px-4">
                <i class="bi bi-plus-circle me-2"></i>
                CADASTRAR ARENA
            </span>
        </a>
    @endif

    @include('client.arenas._live-search')

</div>

@endsection

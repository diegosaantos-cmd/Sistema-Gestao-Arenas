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

        <div class="col-md-3">
            <div class="dashboard-card">

                <div>
                    <h5>Agendamentos próximos</h5>
                    <h2>{{ $proximosCount ?? 0 }}</h2>
                </div>

                <i class="bi bi-calendar-check dashboard-icon text-secondary"></i>

            </div>
        </div>

        <div class="col-md-3">
            <div class="dashboard-card">

                <div>
                    <h5>Agendamentos hoje</h5>
                    <h2>{{ $hojeCount ?? 0 }}</h2>
                </div>

                <i class="bi bi-calendar-event dashboard-icon text-primary"></i>

            </div>
        </div>

        <div class="col-md-3">
            <div class="dashboard-card">

                <div>
                    <h5>Agendamentos pendentes</h5>
                    <h2>{{ $pendentes ?? 0 }}</h2>
                </div>

                <i class="bi bi-three-dots dashboard-icon text-warning"></i>

            </div>
        </div>

        <div class="col-md-3">
            <div class="dashboard-card">

                <div>
                    <h5>Agendamentos confirmados</h5>
                    <h2>{{ $confirmados ?? 0 }}</h2>
                </div>

                <i class="bi bi-check-circle dashboard-icon text-success"></i>

            </div>
        </div>

    </div>

    <!-- Conteúdo Principal -->
    <div class="row g-4">

        <!-- Agendamentos -->
        <div class="col-lg-8">

            <div class="dashboard-box">

                <h2 class="section-title">
                    Próximos Agendamentos
                    <span class="badge bg-secondary fs-6 align-middle">{{ $proximosCount ?? 0 }}</span>
                </h2>

                @php
                    $badges = [
                        'pending'   => ['Pendente', 'bg-warning text-dark'],
                        'confirmed' => ['Confirmada', 'bg-success'],
                    ];
                @endphp

                @forelse ($proximos ?? [] as $b)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <div>
                                <span class="text-muted">Arena:</span>
                                <strong>{{ $b->court->arena->name ?? '—' }}</strong>
                            </div>
                            <div>
                                <span class="text-muted">Quadra:</span>
                                <strong>{{ $b->court->name ?? '—' }}</strong>
                            </div>
                            <div class="text-muted small mt-1">
                                <i class="bi bi-calendar-event me-1"></i>{{ $b->date->format('d/m/Y') }}
                                · {{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}
                            </div>
                        </div>
                        @php [$rotulo, $cor] = $badges[$b->status] ?? [$b->status, 'bg-secondary']; @endphp
                        <span class="badge {{ $cor }}">{{ $rotulo }}</span>
                    </div>
                @empty
                    <p class="text-muted">
                        Nenhum agendamento próximo
                    </p>
                @endforelse

                <a href="{{ route('client.bookings.index') }}" class="btn dashboard-btn-outline w-100 mt-4">
                    VER TODOS OS AGENDAMENTOS
                </a>

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

            @if(auth()->user()->type === 'client')

                <!-- Cliente -->
                <div class="arena-owner-card">

                    <h3>
                        <i class="bi bi-trophy-fill me-2"></i>
                        Tem uma Arena?
                    </h3>

                    <p>
                        Cadastre sua arena e gerencie quadras,
                        agendamentos e funcionários como proprietário.
                    </p>

                    <a href="{{ route('owners.create') }}"
                       class="btn btn-warning w-100">

                        <i class="bi bi-plus-circle me-2"></i>
                        Become an Owner

                    </a>

                </div>
            @endif
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

</div>

@endsection
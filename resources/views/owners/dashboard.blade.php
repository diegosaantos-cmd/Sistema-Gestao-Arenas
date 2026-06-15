@extends('layouts.main')

@section('title', 'Owner Dashboard')

@section('content')

<div class="container py-4">

    <div class="mb-4">
        <h1 class="fw-bold">
            Bem-vindo, {{ auth()->user()->name }}!
        </h1>

        <p class="text-muted fs-4">
            Gerencie suas arenas, quadras, reservas e funcionários
        </p>
    </div>

    @if ($selectedArena)
        <div class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="fs-5">
                🏟 Gerenciando: <strong>{{ $selectedArena->name }}</strong>
            </div>

            @if ($arenasCount > 1)
                <form action="{{ route('owners.arena.select') }}" method="POST"
                      class="d-flex align-items-center gap-2 mb-0">
                    @csrf
                    <label class="mb-0 small text-nowrap">Trocar de arena:</label>
                    <select name="arena_id" class="form-select form-select-sm"
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

    <!-- Cards -->
    <div class="row g-4 mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="text-secondary">Arenas</h4>
                    <h1 class="fw-bold">{{ $arenasCount }}</h1>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="text-secondary">Quadras</h4>
                    <h1 class="fw-bold">{{ $courtsCount }}</h1>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="text-secondary">Today's Bookings</h4>
                    <h1 class="fw-bold">0</h1>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="text-secondary">Clientes</h4>
                    <h1 class="fw-bold">{{ $customersCount }}</h1>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="text-secondary">Employees</h4>
                    <h1 class="fw-bold">2</h1>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="text-secondary">Monthly Revenue</h4>
                    <h1 class="fw-bold text-success">
                        R$ 390,00
                    </h1>
                </div>
            </div>
        </div>

    </div>

    <!-- Actions + Bookings -->
    <div class="row">

        <div class="col-lg-6 mb-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h2 class="fw-bold mb-4">
                        Açoes Rápidas
                    </h2>

                    <div class="d-grid gap-3">

                        <a href="{{ route('arenas.create') }}"
                            class="btn btn-outline-dark btn-lg">
                            🏟 Nova Arena
                        </a>

                        <a href="{{ route('quadras.create') }}"
                            class="btn btn-outline-dark btn-lg">
                            ⚽ Nova Quadra
                        </a>

                        <a href="#"
                            class="btn btn-outline-dark btn-lg">
                            👤 Novo Funcionário
                        </a>

                        <a href="#"
                            class="btn btn-outline-dark btn-lg">
                            💰 Abrir Caixa
                        </a>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-6 mb-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h2 class="fw-bold mb-4">
                        Proximos Agendamentos
                        <span class="badge bg-secondary fs-6 align-middle">
                            {{ $proximosAgendamentos->count() }}
                        </span>
                    </h2>

                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Quadra</th>
                                <th>Data</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($proximosAgendamentos as $i => $booking)
                                <tr class="{{ $i >= 5 ? 'agendamento-extra d-none' : '' }}">
                                    <td>{{ $booking->client->user->name }}</td>
                                    <td>{{ $booking->court->name }}</td>
                                    <td>
                                        {{ $booking->date->format('d/m/Y') }}
                                        {{ substr($booking->start_time, 0, 5) }}
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                data-editar-agendamento
                                                data-booking-id="{{ $booking->id }}">
                                            Editar horário
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-cancelar-agendamento
                                                data-booking-id="{{ $booking->id }}">
                                            Cancelar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        Nenhum agendamento por enquanto
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                    @if ($proximosAgendamentos->count() > 5)
                        <div class="text-center">
                            <button type="button"
                                    class="btn btn-outline-dark btn-sm"
                                    data-toggle-agendamentos
                                    data-total="{{ $proximosAgendamentos->count() }}">
                                Ver todos ({{ $proximosAgendamentos->count() }})
                            </button>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var btn = document.querySelector('[data-toggle-agendamentos]');
                                if (!btn) return;

                                btn.addEventListener('click', function () {
                                    var extras = document.querySelectorAll('.agendamento-extra');
                                    var expandido = btn.dataset.expandido === '1';

                                    extras.forEach(function (linha) {
                                        linha.classList.toggle('d-none', expandido);
                                    });

                                    btn.dataset.expandido = expandido ? '0' : '1';
                                    btn.textContent = expandido
                                        ? 'Ver todos (' + btn.dataset.total + ')'
                                        : 'Ver menos';
                                });
                            });
                        </script>
                    @endif

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
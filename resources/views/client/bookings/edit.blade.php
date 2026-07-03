@extends('layouts.main')

@section('title', 'Editar agendamento')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <div class="mb-3">
        <a href="{{ route('client.bookings.index') }}" class="btn btn-dark btn-sm">
            ← Voltar aos agendamentos
        </a>
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Editar agendamento</h1>
        <p class="dashboard-subtitle mb-0">
            {{ $arena->name }} · {{ $court->name }}
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="dashboard-box mb-4">
                <h2 class="section-title" style="font-size: 1.4rem;">1. Escolha o dia</h2>

                <form method="GET" action="{{ route('client.bookings.edit', $booking) }}"
                      class="d-flex gap-2 flex-wrap">
                    <input type="date" name="date" value="{{ $date }}"
                           min="{{ now()->toDateString() }}"
                           class="form-control" style="max-width: 220px;" required>
                    <button class="btn dashboard-btn-primary px-4">
                        <i class="bi bi-search me-2"></i> Ver horários
                    </button>
                </form>
            </div>

            @if (! $aberto)
                <div class="dashboard-box text-center text-danger fw-bold">
                    <i class="bi bi-door-closed me-2"></i>
                    A arena está fechada nesta data. Escolha outro dia.
                </div>
            @elseif ($slots->isEmpty())
                <div class="dashboard-box text-center text-muted">
                    Não há horários disponíveis neste dia.
                </div>
            @else
                <form method="POST" action="{{ route('client.bookings.update', $booking) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="date" value="{{ $date }}">

                    <div class="dashboard-box">
                        <h2 class="section-title mb-1" style="font-size: 1.4rem;">2. Escolha o novo horário</h2>
                        <p class="text-muted">
                            Alterações são permitidas somente com pelo menos 1 hora de antecedência.
                        </p>

                        @php
                            $horarioAtual = substr($booking->start_time, 0, 5) . '-' . substr($booking->end_time, 0, 5);
                            $marcado = old('horario', $date === $booking->date->toDateString() ? $horarioAtual : '');
                        @endphp

                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($slots as $slot)
                                @php $valor = $slot['start'] . '-' . $slot['end']; @endphp

                                @if ($slot['ocupado'])
                                    <span class="rounded px-3 py-2"
                                          style="background:#021B35;color:#fff;cursor:not-allowed;text-decoration:line-through;">
                                        {{ $slot['start'] }}–{{ $slot['end'] }}
                                    </span>
                                @else
                                    <label class="border rounded px-3 py-2" style="cursor:pointer;">
                                        <input type="radio" name="horario" value="{{ $valor }}"
                                               {{ $marcado === $valor ? 'checked' : '' }} required>
                                        {{ $slot['start'] }}–{{ $slot['end'] }}
                                    </label>
                                @endif
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-success mt-4 px-4">
                            <i class="bi bi-check-circle me-2"></i> Salvar alteração
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="dashboard-box">
                <h2 class="section-title" style="font-size: 1.2rem;">Agendamento atual</h2>
                <p class="mb-1"><strong>Data:</strong> {{ $booking->date->format('d/m/Y') }}</p>
                <p class="mb-0">
                    <strong>Horário:</strong>
                    {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
                </p>
            </div>
        </div>
    </div>
</div>

@endsection

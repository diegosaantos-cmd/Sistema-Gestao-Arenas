@extends('layouts.main')

@section('title', 'Editar agendamento')

@section('content')

<div class="container py-4">

    @php
        $origensValidas = ['client.bookings.index', 'client.bookings.pending', 'client.bookings.today'];
        $origem = in_array(request('from'), $origensValidas) ? request('from') : 'client.bookings.index';
    @endphp

    <a href="{{ route($origem) }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar aos agendamentos
    </a>

    <h1 class="fw-bold mb-1">Editar agendamento #{{ $booking->id }}</h1>
    <p class="text-muted">
        {{ $arena->name }} · Quadra <strong>{{ $court->name }}</strong>
    </p>

    <div class="alert alert-secondary">
        <strong>Agendamento atual:</strong>
        {{ $booking->date->format('d/m/Y') }},
        {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
    </div>

    <div class="alert alert-info alert-dismissible fade show">
        As alterações seguem o <strong>mesmo prazo do cancelamento gratuito</strong> da arena.
        Passado esse prazo (quando o cancelamento já teria taxa), a edição fica bloqueada.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @php
        $diasNome = [
            0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira',
            3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado',
        ];
    @endphp

    <div class="row g-4">
        <div class="col-lg-8">

            {{-- Passo 1: escolher o dia --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">1. Escolha o novo dia</h5>
                    <form method="GET" action="{{ route('client.bookings.edit', $booking) }}"
                          class="d-flex gap-2 flex-wrap">
                        <input type="hidden" name="from" value="{{ $origem }}">
                        <input type="date" name="date" value="{{ $date }}"
                               min="{{ now()->toDateString() }}"
                               class="form-control" style="max-width: 220px;" required>
                        <button class="btn btn-primary px-4">
                            <i class="bi bi-search me-1"></i> Ver horários
                        </button>
                    </form>
                </div>
            </div>

            {{-- Passo 2: escolher o horário --}}
            @if (! $aberto)
                @php $d = \Carbon\Carbon::parse($date); @endphp
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center text-danger fw-bold">
                        <i class="bi bi-door-closed me-2"></i>
                        A arena está fechada em {{ $diasNome[$d->dayOfWeek] }}, {{ $d->format('d/m/Y') }}.
                        Escolha outro dia.
                    </div>
                </div>
            @else
                @php $temLivre = $slots->contains(fn ($s) => ! $s['ocupado']); @endphp
                <form method="POST" action="{{ route('client.bookings.update', $booking) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="date" value="{{ $date }}">
                    <input type="hidden" name="from" value="{{ $origem }}">

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            @php $dSel = \Carbon\Carbon::parse($date); @endphp
                            <h5 class="fw-bold mb-1">2. Escolha o novo horário</h5>
                            <p class="fw-bold mb-3" style="color:#021B35;">
                                <i class="bi bi-calendar-event me-1"></i>
                                {{ $diasNome[$dSel->dayOfWeek] }}, {{ $dSel->format('d/m/Y') }}
                            </p>

                            <div class="d-flex flex-wrap gap-3 mb-3 small text-muted">
                                <span><span class="d-inline-block border rounded me-1" style="width:14px;height:14px;vertical-align:middle;background:#fff;"></span> Livre</span>
                                <span><span class="d-inline-block rounded me-1" style="width:14px;height:14px;vertical-align:middle;background:#021B35;"></span> Ocupado</span>
                            </div>

                            @if ($slots->isEmpty())
                                <p class="text-muted mb-0">Não há horários disponíveis neste dia.</p>
                            @else
                                @php
                                    $horarioAtual = substr($booking->start_time, 0, 5) . '-' . substr($booking->end_time, 0, 5);
                                    $marcado = old('horario', $date === $booking->date->toDateString() ? $horarioAtual : '');
                                @endphp
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($slots as $slot)
                                        @if ($slot['ocupado'])
                                            <span class="rounded px-3 py-2"
                                                  style="background:#021B35; color:#fff; cursor:not-allowed; text-decoration:line-through;"
                                                  title="Horário já reservado">
                                                {{ $slot['start'] }}–{{ $slot['end'] }}
                                            </span>
                                        @else
                                            @php $valor = $slot['start'] . '-' . $slot['end']; @endphp
                                            <label class="border rounded px-3 py-2" style="cursor: pointer;">
                                                <input type="radio" name="horario" value="{{ $valor }}"
                                                       {{ $marcado === $valor ? 'checked' : '' }} required>
                                                {{ $slot['start'] }}–{{ $slot['end'] }}
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($temLivre)
                        <div class="d-flex gap-2">
                            <a href="{{ route($origem) }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i> Salvar alteração
                            </button>
                        </div>
                    @endif
                </form>
            @endif

        </div>

        {{-- Lateral: dias de funcionamento --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">Dias de funcionamento</h6>
                    @if (empty($diasAbertos))
                        <p class="text-muted mb-0">Esta arena ainda não definiu horários.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($diasNome as $num => $nome)
                                <li class="d-flex justify-content-between border-bottom py-1">
                                    <span>{{ $nome }}</span>
                                    <span>
                                        @if (isset($diasAbertos[$num]))
                                            {{ $diasAbertos[$num] }}
                                        @else
                                            <span class="text-muted">Fechado</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
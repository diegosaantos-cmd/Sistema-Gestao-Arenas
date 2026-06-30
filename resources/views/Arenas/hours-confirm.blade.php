@extends('layouts.main')

@section('title', 'Confirmar mudança de horários')

@section('content')

<div class="container py-4">

    <a href="{{ route('arenas.show', $arena->id) }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar (não alterar)
    </a>

    <h1 class="fw-bold mb-3">Confirmar mudança de horários — {{ $arena->name }}</h1>

    @if (! empty($erros))
        <div class="alert alert-danger">
            <strong>Não foi possível continuar.</strong>
            <ul class="mb-0 mt-1">
                @foreach ($erros as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-warning">
        Os agendamentos abaixo ficam <strong>fora do novo horário</strong> e serão
        <strong>cancelados</strong>. Os clientes precisarão reagendar. Informe o motivo
        (será aplicado a todos) e confirme para salvar os horários e cancelar essas reservas.
    </div>

    @php
        $statusInfo = [
            'pending'   => ['Pendente',   'bg-warning text-dark'],
            'confirmed' => ['Confirmada', 'bg-success'],
        ];
        $dias = [
            0 => 'Dom', 1 => 'Seg', 2 => 'Ter', 3 => 'Qua',
            4 => 'Qui', 5 => 'Sex', 6 => 'Sáb',
        ];
    @endphp

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Quadra</th>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($afetados as $b)
                        <tr>
                            <td>{{ $b->client->user->name ?? '—' }}</td>
                            <td>{{ $b->court->name ?? '—' }}</td>
                            <td>{{ $dias[$b->date->dayOfWeek] }}, {{ $b->date->format('d/m/Y') }}</td>
                            <td>{{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}</td>
                            <td>
                                @php $st = $statusInfo[$b->status] ?? [$b->status, 'bg-secondary']; @endphp
                                <span class="badge {{ $st[1] }}">{{ $st[0] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('arenas.hours.confirm', $arena->id) }}">
        @csrf

        {{-- Horários propostos, reenviados --}}
        @foreach ($horarios as $dia => $info)
            @if (! empty($info['aberto']))
                <input type="hidden" name="horarios[{{ $dia }}][aberto]" value="1">
            @endif
            @foreach (['p1_abre', 'p1_fecha', 'p2_abre', 'p2_fecha'] as $campo)
                @if (! empty($info[$campo]))
                    <input type="hidden" name="horarios[{{ $dia }}][{{ $campo }}]" value="{{ $info[$campo] }}">
                @endif
            @endforeach
        @endforeach

        <div class="mb-3" style="max-width: 600px;">
            <label class="form-label fw-bold">Motivo do cancelamento (para todas)</label>
            <textarea name="motivo" class="form-control" rows="3" required
                      placeholder="Ex.: Mudança no horário de funcionamento da arena.">{{ $motivo ?? '' }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('arenas.show', $arena->id) }}" class="btn btn-outline-secondary">
                Voltar (não alterar)
            </a>
            <button type="submit" class="btn btn-danger">
                Confirmar cancelamentos e salvar horários
            </button>
        </div>
    </form>

</div>

@endsection

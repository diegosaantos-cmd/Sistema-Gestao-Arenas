@extends('layouts.main')

@section('title', 'Confirmar exclusão da arena')

@section('content')

<div class="container py-4">

    <a href="{{ route('arenas.show', $arena->id) }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar (não excluir)
    </a>

    <h1 class="fw-bold mb-3">Excluir arena — {{ $arena->name }}</h1>

    @if (! empty($erroMotivo))
        <div class="alert alert-danger">
            <strong>Não foi possível continuar.</strong> {{ $erroMotivo }}
        </div>
    @endif

    <div class="alert alert-danger">
        A arena será <strong>excluída</strong> (sai da sua gestão e dos catálogos) e os agendamentos
        abaixo serão <strong>cancelados</strong>. O <strong>histórico de reservas é mantido</strong>.
        Informe o motivo (será aplicado a todos) e confirme. <strong>Esta ação não pode ser desfeita.</strong>
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
                            <td>{{ $b->nomeCliente() }}</td>
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

    <form method="POST" action="{{ route('arenas.delete.confirm', $arena->id) }}">
        @csrf

        <div class="mb-3" style="max-width: 600px;">
            <label class="form-label fw-bold">Motivo do cancelamento (para todas)</label>
            <textarea name="motivo" class="form-control" rows="3" required
                      placeholder="Ex.: Arena encerrada.">{{ $motivo ?? '' }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('arenas.show', $arena->id) }}" class="btn btn-secondary">
                Voltar (não excluir)
            </a>
            <button type="submit" class="btn btn-danger">
                🗑️ Confirmar cancelamentos e excluir arena
            </button>
        </div>
    </form>

</div>

@endsection
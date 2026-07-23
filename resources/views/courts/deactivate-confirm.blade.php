@extends('layouts.main')

@section('title', 'Confirmar desativação da quadra')

@section('content')

<div class="container py-4 painel">

    <x-back :href="route('quadras.index')" />

    <h1 class="fw-bold mb-1">Desativar quadra — {{ $quadra->name }}</h1>
    <p class="text-muted">Arena: <strong>{{ $arena->name }}</strong></p>

    @if (! empty($erroMotivo))
        <div class="alert alert-danger">
            <strong>Não foi possível continuar.</strong> {{ $erroMotivo }}
        </div>
    @endif

    <div class="alert alert-warning">
        Ao desativar esta quadra, ela <strong>deixa de aparecer para novos agendamentos</strong>
        e os agendamentos abaixo serão <strong>cancelados</strong>. Os clientes precisarão
        reagendar. Informe o motivo (será aplicado a todos) e confirme.
    </div>

    {{-- Impacto financeiro: cancelar reembolsa as pagas (cancelarEmLote). --}}
    @if (($reembolsos['quantidade'] ?? 0) > 0)
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="bi bi-cash-coin fs-5"></i>
            <div>
                <strong>
                    {{ $reembolsos['quantidade'] }}
                    {{ $reembolsos['quantidade'] === 1 ? 'reserva paga será reembolsada' : 'reservas pagas serão reembolsadas' }},
                    somando R$ {{ number_format($reembolsos['total'], 2, ',', '.') }}.
                </strong>
                <div class="small">A devolução é <strong>integral</strong>.</div>
            </div>
        </div>
    @endif

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
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Status</th>
                        <th>Pagamento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($afetados as $b)
                        @php $pago = ($reembolsos['pagos'] ?? collect())->get($b->id); @endphp
                        <tr>
                            <td>{{ $b->nomeCliente() }}</td>
                            <td>{{ $dias[$b->date->dayOfWeek] }}, {{ $b->date->format('d/m/Y') }}</td>
                            <td>{{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}</td>
                            <td>
                                @php $st = $statusInfo[$b->status] ?? [$b->status, 'bg-secondary']; @endphp
                                <span class="badge {{ $st[1] }}">{{ $st[0] }}</span>
                            </td>
                            <td>
                                @if ($pago)
                                    <span class="text-success fw-semibold">Pago R$ {{ number_format($pago, 2, ',', '.') }}</span>
                                    <div class="small text-muted">será reembolsado</div>
                                @else
                                    <span class="text-muted">Não pago</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('quadras.deactivate.confirm', $quadra) }}">
        @csrf

        <div class="mb-3" style="max-width: 600px;">
            <label class="form-label fw-bold">Motivo do cancelamento (para todas)</label>
            <textarea name="motivo" class="form-control" rows="3" required
                      placeholder="Ex.: Quadra em manutenção.">{{ $motivo ?? '' }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('quadras.index') }}" class="btn btn-secondary">
                Voltar (não desativar)
            </a>
            <button type="submit" class="btn btn-danger">
                Confirmar cancelamentos e desativar
            </button>
        </div>
    </form>

</div>

@endsection
@extends('layouts.main')

@section('title', 'Confirmar exclusão da arena')

@section('content')

<div class="container py-4">

    <x-back :href="route('arenas.show', $arena->id)" />

    <h1 class="fw-bold mb-3">Excluir arena — {{ $arena->name }}</h1>

    @if (! empty($erroMotivo))
        <div class="alert alert-danger">
            <strong>Não foi possível continuar.</strong> {{ $erroMotivo }}
        </div>
    @endif

    @if (! empty($ultima))
        {{-- Sem nenhuma arena o proprietário não tem mais função no sistema, então
             a conta e a empresa são encerradas junto. Precisa ser dito ANTES. --}}
        <div class="alert alert-danger border-2">
            <h5 class="alert-heading fw-bold">
                <i class="bi bi-exclamation-triangle me-1"></i> Esta é a sua única arena
            </h5>
            <p class="mb-2">
                Ao excluí-la, <strong>sua conta e sua empresa também serão encerradas</strong> —
                você será desconectado e não conseguirá mais entrar.
            </p>
            <p class="mb-0">
                O histórico de reservas, pagamentos e caixa <strong>é preservado</strong>, assim como
                o nome da empresa e o CPF/CNPJ. Seus dados pessoais são apagados, e o seu e-mail fica
                livre caso queira se cadastrar de novo mais tarde.
            </p>
        </div>
    @endif

    <div class="alert alert-danger">
        A arena será <strong>excluída</strong> (sai da sua gestão e dos catálogos)
        @if ($afetados->isNotEmpty())
            e os agendamentos abaixo serão <strong>cancelados</strong>.
            O <strong>histórico de reservas é mantido</strong>. Informe o motivo (será aplicado a todos) e confirme.
        @else
            . O <strong>histórico de reservas é mantido</strong>.
        @endif
        <strong>Esta ação não pode ser desfeita.</strong>
    </div>

    {{-- Impacto financeiro explícito: quantas reservas pagas serão reembolsadas
         e quanto no total. Só aparece se houver alguma paga — a exclusão pela
         arena devolve integral (RN05). --}}
    @if (($reembolsos['quantidade'] ?? 0) > 0)
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="bi bi-cash-coin fs-5"></i>
            <div>
                <strong>
                    {{ $reembolsos['quantidade'] }}
                    {{ $reembolsos['quantidade'] === 1 ? 'reserva paga será reembolsada' : 'reservas pagas serão reembolsadas' }},
                    somando R$ {{ number_format($reembolsos['total'], 2, ',', '.') }}.
                </strong>
                <div class="small">
                    A devolução é <strong>integral</strong>.
                </div>
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
                        <th>Quadra</th>
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
                            <td>{{ $b->court->name ?? '—' }}</td>
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

    <form method="POST" action="{{ route('arenas.delete.confirm', $arena->id) }}">
        @csrf

        {{-- Só pede motivo se houver reserva para cancelar: o motivo vai no aviso
             ao cliente. Sem reservas, a confirmação existe só para o alerta da
             última arena, e exigir texto travaria o dono sem necessidade. --}}
        @if ($afetados->isNotEmpty())
            <div class="mb-3" style="max-width: 600px;">
                <label class="form-label fw-bold">Motivo do cancelamento (para todas)</label>
                <textarea name="motivo" class="form-control" rows="3" required
                          placeholder="Ex.: Arena encerrada.">{{ $motivo ?? '' }}</textarea>
            </div>
        @endif

        <div class="d-flex gap-2">
            <a href="{{ route('arenas.show', $arena->id) }}" class="btn btn-secondary">
                Voltar (não excluir)
            </a>
            <button type="submit" class="btn btn-danger">
                @if (! empty($ultima))
                    🗑️ Excluir arena e encerrar minha conta
                @else
                    🗑️ Confirmar cancelamentos e excluir arena
                @endif
            </button>
        </div>
    </form>

</div>

@endsection
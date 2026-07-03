@extends('layouts.main')

@section('title', 'Caixas fechados')

@section('content')

<div class="container py-4">

    <a href="{{ route('caixa.index') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao caixa
    </a>

    <h1 class="fw-bold mb-1">Caixas fechados</h1>
    <p class="text-muted">Histórico de caixas da arena <strong>{{ $arena->name }}</strong>.</p>

    @if ($meses->isNotEmpty())
        <form method="GET" class="mb-3 d-flex align-items-center gap-2 flex-wrap">
            <label class="mb-0 small text-nowrap">Filtrar por mês:</label>
            <select name="mes" class="form-select" style="max-width: 220px;"
                    onchange="this.form.submit()">
                <option value="">Todos os meses</option>
                @foreach ($meses as $m)
                    <option value="{{ $m['valor'] }}" {{ $mesSelecionado === $m['valor'] ? 'selected' : '' }}>
                        {{ $m['label'] }}
                    </option>
                @endforeach
            </select>
            @if ($mesSelecionado)
                <a href="{{ route('caixa.closed') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
            @endif
        </form>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Aberto</th>
                            <th>Fechado</th>
                            <th>Operador</th>
                            <th class="text-end">Saldo final</th>
                            <th class="text-end">Relatório</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($caixasFechados as $c)
                            <tr>
                                <td class="text-nowrap">{{ optional($c->opened_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-nowrap">{{ optional($c->closed_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $c->user->name ?? '—' }}</td>
                                <td class="text-end fw-semibold">R$ {{ number_format($c->closing_balance, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('caixa.show', $c) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-info-circle me-1"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Nenhum caixa fechado ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection
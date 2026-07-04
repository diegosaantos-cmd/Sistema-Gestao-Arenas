@extends('layouts.main')

@section('title', 'Financeiro do mês')

@section('content')

<div class="container py-4">

    @php
        $veioDoBalanco = request('from') === 'balanco';
        $voltarUrl = $veioDoBalanco ? route('caixa.balance') : route('owners.dashboard');
        $voltarLabel = $veioDoBalanco ? '← Voltar ao balanço' : '← Voltar ao painel';
    @endphp
    <a href="{{ $voltarUrl }}" class="btn btn-dark btn-sm mb-3">
        {{ $voltarLabel }}
    </a>

    <h1 class="fw-bold mb-1">Financeiro do mês</h1>
    <p class="text-muted">Entradas, saídas e lucro da arena <strong>{{ $arena->name }}</strong>.</p>

    @if ($meses->isEmpty())
        <div class="card shadow-sm border-0">
            <div class="card-body text-center text-muted py-5">
                Nenhum lançamento no caixa ainda.
            </div>
        </div>
    @else

        <form method="GET" class="mb-4 d-flex align-items-center gap-2 flex-wrap">
            <label class="mb-0 small text-nowrap">Mês:</label>
            <select name="mes" class="form-select" style="max-width: 220px;"
                    onchange="this.form.submit()">
                @foreach ($meses as $m)
                    <option value="{{ $m['valor'] }}" {{ $mesSelecionado === $m['valor'] ? 'selected' : '' }}>
                        {{ $m['label'] }}
                    </option>
                @endforeach
            </select>
        </form>

        {{-- Resumo: entradas, saídas, lucro --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body">
                        <h6 class="text-secondary mb-1">Entradas</h6>
                        <h2 class="fw-bold text-success mb-0">+ R$ {{ number_format($entradas, 2, ',', '.') }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body">
                        <h6 class="text-secondary mb-1">Saídas</h6>
                        <h2 class="fw-bold text-danger mb-0">− R$ {{ number_format($saidas, 2, ',', '.') }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 text-center bg-light">
                    <div class="card-body">
                        <h6 class="text-secondary mb-1">Lucro — {{ $mesLabel }}</h6>
                        <h2 class="fw-bold mb-0 {{ $lucro >= 0 ? 'text-success' : 'text-danger' }}">
                            R$ {{ number_format($lucro, 2, ',', '.') }}
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lançamentos do mês (5 mais recentes) --}}
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Lançamentos de {{ $mesLabel }}</h5>
                    @if ($totalLancamentos > 5)
                        <a href="{{ route('caixa.report.entries', ['mes' => $mesSelecionado, 'from' => request('from')]) }}"
                           class="btn btn-outline-dark btn-sm">
                            Ver todos ({{ $totalLancamentos }})
                        </a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Quando</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Caixa</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lancamentos as $entry)
                                <tr>
                                    <td class="text-nowrap">{{ optional($entry->created_at)->format('d/m H:i') ?? '—' }}</td>
                                    <td>
                                        @if ($entry->type === 'income')
                                            <span class="badge bg-success">Entrada</span>
                                        @else
                                            <span class="badge bg-danger">Saída</span>
                                        @endif
                                    </td>
                                    <td>{{ $entry->description }}</td>
                                    <td class="text-nowrap">
                                        Caixa #{{ $numeros[$entry->cash_register_id] ?? $entry->cash_register_id }}
                                        @if ($entry->cashRegister)
                                            <div class="small text-muted">
                                                {{ optional($entry->cashRegister->opened_at)->format('d/m/Y H:i') ?? '—' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold {{ $entry->type === 'income' ? 'text-success' : 'text-danger' }}">
                                        {{ $entry->type === 'income' ? '+' : '−' }} R$ {{ number_format($entry->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nenhum lançamento neste mês.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @endif

</div>

@endsection
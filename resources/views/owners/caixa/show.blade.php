@extends('layouts.main')

@section('title', 'Relatório do caixa')

@section('content')

<div class="container py-4">

    <a href="{{ route('caixa.index') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao caixa
    </a>

    <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="fw-bold mb-0">Caixa #{{ $caixa->id }}</h1>
        <span class="badge bg-secondary">Fechado</span>
    </div>
    <p class="text-muted">Arena: <strong>{{ $arena->name }}</strong></p>

    {{-- Resumo --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Operador:</strong> {{ $caixa->user->name ?? '—' }}</p>
                    <p class="mb-1"><strong>Aberto em:</strong> {{ optional($caixa->opened_at)->format('d/m/Y H:i') ?? '—' }}</p>
                    <p class="mb-0"><strong>Fechado em:</strong> {{ optional($caixa->closed_at)->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
                <div class="col-md-6">
                    @if ($caixa->notes)
                        <p class="mb-1"><strong>Observação:</strong></p>
                        <p class="mb-0 text-muted">{{ $caixa->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="row text-center g-3">
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 h-100">
                        <div class="text-muted small">Troco inicial</div>
                        <div class="fw-bold">R$ {{ number_format($caixa->opening_balance, 2, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 h-100">
                        <div class="text-muted small">Entradas</div>
                        <div class="fw-bold text-success">+ R$ {{ number_format($caixa->totalEntradas(), 2, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 h-100">
                        <div class="text-muted small">Saídas</div>
                        <div class="fw-bold text-danger">− R$ {{ number_format($caixa->totalSaidas(), 2, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 h-100 bg-light">
                        <div class="text-muted small">Saldo final</div>
                        <div class="fw-bold fs-5">R$ {{ number_format($caixa->closing_balance, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lançamentos --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Lançamentos</h5>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Quando</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($caixa->entries as $entry)
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
                                <td class="text-end fw-semibold {{ $entry->type === 'income' ? 'text-success' : 'text-danger' }}">
                                    {{ $entry->type === 'income' ? '+' : '−' }} R$ {{ number_format($entry->amount, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Nenhum lançamento.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection

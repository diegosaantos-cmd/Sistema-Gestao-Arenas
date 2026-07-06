@extends('layouts.main')

@section('title', 'Lançamentos do mês')

@section('content')

<div class="container py-4">

    <a href="{{ route('caixa.report', ['mes' => $mesSelecionado, 'from' => request('from')]) }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao financeiro
    </a>

    <h1 class="fw-bold mb-1">Lançamentos de {{ $mesLabel }}</h1>
    <p class="text-muted">
        Arena <strong>{{ $arena->name }}</strong>
        <span class="mx-2">·</span>
        <span class="text-success">+ R$ {{ number_format($entradas, 2, ',', '.') }}</span>
        <span class="text-danger">− R$ {{ number_format($saidas, 2, ',', '.') }}</span>
        <span class="mx-2">·</span>
        Lucro: <strong class="{{ $lucro >= 0 ? 'text-success' : 'text-danger' }}">R$ {{ number_format($lucro, 2, ',', '.') }}</strong>
    </p>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Quando</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Caixa</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Detalhes</th>
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
                                <td class="text-end">
                                    <a href="{{ route('caixa.entry.show', ['entry' => $entry, 'voltar' => request()->fullUrl()]) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-info-circle"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nenhum lançamento neste mês.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection
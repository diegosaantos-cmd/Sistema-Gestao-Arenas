@extends('layouts.main')

@section('title', 'Lançamentos')

@section('content')

<div class="container py-4 painel">

    <x-back :href="route('caixa.index')" />

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
        <h1 class="fw-bold mb-0">Lançamentos</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novaEntrada">
                + Entrada
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#novaSaida">
                − Saída
            </button>
        </div>
    </div>
    <p class="text-muted">
        Saldo atual: <strong>R$ {{ number_format($caixaAberto->saldoAtual(), 2, ',', '.') }}</strong>
        <span class="mx-2">·</span>
        <span class="text-success">+ R$ {{ number_format($caixaAberto->totalEntradas(), 2, ',', '.') }}</span>
        <span class="text-danger">− R$ {{ number_format($caixaAberto->totalSaidas(), 2, ',', '.') }}</span>
    </p>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

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

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nº</th>
                            <th>Quando</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($caixaAberto->entries as $entry)
                            <tr>
                                <td class="fw-semibold text-nowrap">#{{ $numerosLancamentos[$entry->id] ?? $entry->id }}</td>
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
                                <td class="text-end">
                                    <a href="{{ route('caixa.entry.show', ['entry' => $entry, 'voltar' => request()->fullUrl()]) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-info-circle"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Nenhum lançamento ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Nova entrada --}}
<div class="modal fade" id="novaEntrada" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('caixa.entry') }}" class="modal-content">
            @csrf
            <input type="hidden" name="type" value="income">
            <div class="modal-header">
                <h5 class="modal-title">Nova entrada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Valor (R$)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                </div>
                <div class="mb-0">
                    <label class="form-label">Descrição</label>
                    <input type="text" name="description" class="form-control" maxlength="255" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Registrar</button>
            </div>
        </form>
    </div>
</div>

{{-- Nova saída --}}
<div class="modal fade" id="novaSaida" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('caixa.entry') }}" class="modal-content">
            @csrf
            <input type="hidden" name="type" value="expense">
            <div class="modal-header">
                <h5 class="modal-title">Nova saída</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Valor (R$)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                </div>
                <div class="mb-0">
                    <label class="form-label">Descrição</label>
                    <input type="text" name="description" class="form-control" maxlength="255" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Registrar</button>
            </div>
        </form>
    </div>
</div>

@endsection
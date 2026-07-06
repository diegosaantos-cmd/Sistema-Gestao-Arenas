@extends('layouts.main')

@section('title', 'Caixa')

@section('content')

<div class="container py-4">

    <a href="{{ route('owners.dashboard') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao painel
    </a>

    <h1 class="fw-bold mb-1">Caixa</h1>
    <p class="text-muted">Arena: <strong>{{ $arena->name }}</strong></p>

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

    @if ($caixaAberto)

        {{-- ============ RESUMO DO CAIXA ABERTO ============ --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <span class="badge bg-success mb-2">Caixa aberto</span>
                        <p class="mb-1"><strong>Aberto por:</strong> {{ $caixaAberto->user->name ?? '—' }}</p>
                        <p class="mb-0 text-muted small">
                            Desde {{ optional($caixaAberto->opened_at)->format('d/m/Y H:i') ?? '—' }}
                        </p>
                    </div>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#fecharCaixa">
                        🔒 Fechar caixa
                    </button>
                </div>

                <div class="row text-center g-3">
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted small">Troco inicial</div>
                            <div class="fw-bold">R$ {{ number_format($caixaAberto->opening_balance, 2, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted small">Entradas</div>
                            <div class="fw-bold text-success">+ R$ {{ number_format($caixaAberto->totalEntradas(), 2, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted small">Saídas</div>
                            <div class="fw-bold text-danger">− R$ {{ number_format($caixaAberto->totalSaidas(), 2, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 h-100 bg-light">
                            <div class="text-muted small">Saldo atual</div>
                            <div class="fw-bold fs-5">R$ {{ number_format($caixaAberto->saldoAtual(), 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ CARDS DAS SEÇÕES ============ --}}
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('caixa.receivables') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover text-center">
                        <div class="card-body">
                            <div class="fs-2">💵</div>
                            <h6 class="text-secondary mb-1">Reservas a receber</h6>
                            <h2 class="fw-bold mb-1">{{ $reservasCount }}</h2>
                            <span class="small text-primary">ver todas →</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('caixa.fees') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover text-center {{ $taxasCount > 0 ? 'border border-danger' : '' }}">
                        <div class="card-body">
                            <div class="fs-2">🚫</div>
                            <h6 class="text-secondary mb-1">Taxas de cancelamento</h6>
                            <h2 class="fw-bold mb-1 {{ $taxasCount > 0 ? 'text-danger' : '' }}">{{ $taxasCount }}</h2>
                            <span class="small text-primary">ver todas →</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('caixa.pending-payments') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover text-center {{ $lancarCount > 0 ? 'border border-warning' : '' }}">
                        <div class="card-body">
                            <div class="fs-2">📥</div>
                            <h6 class="text-secondary mb-1">Pagamentos a lançar</h6>
                            <h2 class="fw-bold mb-1 {{ $lancarCount > 0 ? 'text-warning' : '' }}">{{ $lancarCount }}</h2>
                            <span class="small text-primary">ver todos →</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('caixa.entries') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover text-center">
                        <div class="card-body">
                            <div class="fs-2">📒</div>
                            <h6 class="text-secondary mb-1">Lançamentos</h6>
                            <h2 class="fw-bold mb-1">{{ $lancamentosCount }}</h2>
                            <span class="small text-primary">ver todos →</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('caixa.closed') }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm border-0 h-100 card-hover text-center">
                        <div class="card-body">
                            <div class="fs-2">🔒</div>
                            <h6 class="text-secondary mb-1">Caixas fechados</h6>
                            <h2 class="fw-bold mb-0">{{ $fechadosCount }}</h2>
                            <div class="small text-muted mb-1">{{ $mesAtualLabel }}</div>
                            <span class="small text-primary">ver todos →</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

    @else

        {{-- ============ NENHUM CAIXA ABERTO ============ --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center py-5">
                <div class="fs-1 mb-2">💰</div>
                <h5 class="fw-bold">Nenhum caixa aberto</h5>
                <p class="text-muted">Abra o caixa para registrar pagamentos e movimentações.</p>
                @if ($arena->active)
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#abrirCaixa">
                        Abrir caixa
                    </button>
                @else
                    <p class="text-warning fw-semibold mb-0">
                        ⚠️ Arena inativa — reative-a para abrir o caixa.
                    </p>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 text-center">
            <div class="card-body py-5">
                <div class="fs-1 mb-2">🔒</div>
                <h5 class="fw-bold mb-1">Caixas fechados</h5>
                <h2 class="fw-bold mb-0">{{ $fechadosCount }}</h2>
                <div class="small text-muted mb-3">{{ $mesAtualLabel }}</div>
                <a href="{{ route('caixa.closed') }}" class="btn btn-primary btn-sm">
                    Ver todos
                </a>
            </div>
        </div>

    @endif

</div>

{{-- ==================== MODAIS ==================== --}}

@if ($caixaAberto)

    {{-- Fechar caixa --}}
    <div class="modal fade" id="fecharCaixa" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('caixa.close') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Fechar caixa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-unstyled mb-3">
                        <li class="d-flex justify-content-between"><span>Troco inicial</span><strong>R$ {{ number_format($caixaAberto->opening_balance, 2, ',', '.') }}</strong></li>
                        <li class="d-flex justify-content-between text-success"><span>Entradas</span><strong>+ R$ {{ number_format($caixaAberto->totalEntradas(), 2, ',', '.') }}</strong></li>
                        <li class="d-flex justify-content-between text-danger"><span>Saídas</span><strong>− R$ {{ number_format($caixaAberto->totalSaidas(), 2, ',', '.') }}</strong></li>
                        <li class="d-flex justify-content-between border-top pt-2 mt-2 fs-5"><span>Saldo final</span><strong>R$ {{ number_format($caixaAberto->saldoAtual(), 2, ',', '.') }}</strong></li>
                    </ul>
                    <label class="form-label">Observação (opcional)</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="1000"></textarea>
                    <p class="text-muted small mt-2 mb-0">Após fechar, o caixa vira somente leitura.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Fechar caixa</button>
                </div>
            </form>
        </div>
    </div>

@else

    {{-- Abrir caixa --}}
    <div class="modal fade" id="abrirCaixa" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('caixa.open') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Abrir caixa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Troco inicial (R$)</label>
                        <input type="number" step="0.01" min="0" name="opening_balance" class="form-control" value="0" required>
                        <small class="text-muted">Dinheiro que já está na gaveta ao abrir. Use 0 se não houver.</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Observação (opcional)</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Abrir caixa</button>
                </div>
            </form>
        </div>
    </div>

@endif

@endsection
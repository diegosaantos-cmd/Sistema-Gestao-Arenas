@extends('layouts.main')

@section('title', 'Taxas de cancelamento a receber')

@section('content')

<div class="container py-4">

    <a href="{{ route('caixa.index') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao caixa
    </a>

    <h1 class="fw-bold mb-1">Taxas de cancelamento a receber</h1>
    <p class="text-muted">Reservas canceladas com taxa, aguardando o pagamento do cliente.</p>

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
                            <th>Cliente</th>
                            <th>Quadra</th>
                            <th>Reserva era</th>
                            <th>Cancelada em</th>
                            <th class="text-end">Taxa</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($taxasAReceber as $t)
                            <tr>
                                <td>{{ $t->client->user->name ?? '—' }}</td>
                                <td>{{ $t->court->name ?? '—' }}</td>
                                <td class="text-nowrap">
                                    {{ $t->date->format('d/m/Y') }}
                                    {{ substr($t->start_time, 0, 5) }}–{{ substr($t->end_time, 0, 5) }}
                                </td>
                                <td class="text-nowrap">{{ optional($t->cancelled_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-end fw-semibold text-danger">R$ {{ number_format($t->cancellation_fee_amount, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-success"
                                            data-bs-toggle="modal" data-bs-target="#receberTaxa{{ $t->id }}">
                                        💵 Receber taxa
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Nenhuma taxa de cancelamento a receber.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Modais de receber taxa de cancelamento --}}
@foreach ($taxasAReceber as $t)
    <div class="modal fade" id="receberTaxa{{ $t->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('caixa.pay-fee', $t) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Receber taxa — reserva #{{ $t->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1"><strong>Cliente:</strong> {{ $t->client->user->name ?? '—' }}</p>
                    <p class="mb-3">
                        <strong>Reserva cancelada:</strong> {{ $t->court->name ?? '—' }} —
                        {{ $t->date->format('d/m/Y') }}
                        {{ substr($t->start_time, 0, 5) }}–{{ substr($t->end_time, 0, 5) }}
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Forma de pagamento</label>
                        <select name="payment_method_id" class="form-select" required>
                            <option value="">Selecione…</option>
                            @foreach ($formasPagamento as $forma)
                                <option value="{{ $forma->id }}">{{ $forma->label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-0">
                        <span class="text-muted">Taxa a receber (fixa):</span>
                        <div class="fs-4 fw-bold text-danger">R$ {{ number_format($t->cancellation_fee_amount, 2, ',', '.') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar recebimento</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

@endsection

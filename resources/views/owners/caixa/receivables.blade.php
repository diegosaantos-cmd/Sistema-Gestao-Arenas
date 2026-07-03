@extends('layouts.main')

@section('title', 'Reservas a receber')

@section('content')

<div class="container py-4">

    <a href="{{ route('caixa.index') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao caixa
    </a>

    <h1 class="fw-bold mb-1">Reservas a receber</h1>
    <p class="text-muted">Reservas confirmadas desta arena ainda sem pagamento.</p>

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
                            <th>Data / Horário</th>
                            <th>Situação</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservasAReceber as $reserva)
                            @php
                                $fimReserva = \Carbon\Carbon::parse($reserva->date->toDateString() . ' ' . $reserva->end_time);
                                $jaPassou = now()->greaterThan($fimReserva);
                            @endphp
                            <tr>
                                <td>{{ $reserva->client->user->name ?? '—' }}</td>
                                <td>{{ $reserva->court->name ?? '—' }}</td>
                                <td>
                                    {{ $reserva->date->format('d/m/Y') }}
                                    {{ substr($reserva->start_time, 0, 5) }}–{{ substr($reserva->end_time, 0, 5) }}
                                </td>
                                <td>
                                    @if ($jaPassou)
                                        <span class="badge bg-danger">Realizada</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Agendada</span>
                                    @endif
                                </td>
                                <td class="text-end">R$ {{ number_format($reserva->total_amount, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-success"
                                            data-bs-toggle="modal" data-bs-target="#receber{{ $reserva->id }}">
                                        💵 Receber
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Nenhuma reserva pendente de pagamento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Modais de receber (um por reserva) --}}
@foreach ($reservasAReceber as $reserva)
    <div class="modal fade" id="receber{{ $reserva->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('caixa.pay', $reserva) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Receber reserva #{{ $reserva->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1"><strong>Cliente:</strong> {{ $reserva->client->user->name ?? '—' }}</p>
                    <p class="mb-3">
                        <strong>Quadra:</strong> {{ $reserva->court->name ?? '—' }} —
                        {{ $reserva->date->format('d/m/Y') }}
                        {{ substr($reserva->start_time, 0, 5) }}–{{ substr($reserva->end_time, 0, 5) }}
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
                        <label class="form-label">Valor recebido (R$)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control"
                               value="{{ number_format($reserva->total_amount, 2, '.', '') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar pagamento</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

@endsection
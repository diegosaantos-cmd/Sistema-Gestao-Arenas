@extends('layouts.main')

@section('title', 'Detalhes do lançamento')

@section('content')

<div class="container py-4 painel">

    <x-back :href="request('voltar', route('caixa.index'))" history />

    @php $entrada = $entry->type === 'income'; @endphp

    <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="fw-bold mb-0">Lançamento #{{ $entry->id }}</h1>
        @if ($entrada)
            <span class="badge bg-success">Entrada</span>
        @else
            <span class="badge bg-danger">Saída</span>
        @endif
    </div>
    <p class="fs-3 fw-bold {{ $entrada ? 'text-success' : 'text-danger' }}">
        {{ $entrada ? '+' : '−' }} R$ {{ number_format($entry->amount, 2, ',', '.') }}
    </p>

    <div class="row g-4">

        {{-- Dados do lançamento --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Lançamento</h5>
                    <p class="mb-1"><strong>Tipo:</strong> {{ $entrada ? 'Entrada de dinheiro' : 'Saída de dinheiro' }}</p>
                    <p class="mb-1"><strong>Descrição:</strong> {{ $entry->description }}</p>
                    <p class="mb-1">
                        <strong>Quando:</strong>
                        {{ optional($entry->created_at)->format('d/m/Y H:i') ?? '—' }}
                    </p>
                    <p class="mb-0">
                        <strong>Feito por:</strong>
                        @if ($entry->createdBy)
                            <x-nome-autor :user="$entry->createdBy" com-tipo />
                            @if ($pagamento && $pagamento->origin === 'online')
                                <span class="badge bg-light text-dark border">cliente (online)</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Caixa --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Caixa</h5>
                    <p class="mb-1">
                        <strong>Caixa:</strong>
                        #{{ $numeros[$entry->cash_register_id] ?? $entry->cash_register_id }}
                        <span class="badge {{ $entry->cashRegister->status === 'open' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $entry->cashRegister->status === 'open' ? 'Aberto' : 'Fechado' }}
                        </span>
                    </p>
                    <p class="mb-1">
                        <strong>Aberto em:</strong>
                        {{ optional($entry->cashRegister->opened_at)->format('d/m/Y H:i') ?? '—' }}
                    </p>
                    <p class="mb-0"><strong>Operador do caixa:</strong> {{ $entry->cashRegister->user->name ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Reserva / pagamento (só quando o lançamento é de uma reserva) --}}
        @if ($entry->booking)
            @php $b = $entry->booking; @endphp
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Reserva #{{ $numeroReserva ?? $b->id }}</h5>
                        <p class="mb-1"><strong>Cliente (dono da reserva):</strong> {{ $b->nomeCliente() }}</p>
                        <p class="mb-1"><strong>Quadra:</strong> {{ $b->court->name ?? '—' }}</p>
                        <p class="mb-1">
                            <strong>Data/horário:</strong>
                            {{ optional($b->date)->format('d/m/Y') }}
                            {{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}
                        </p>
                        <p class="mb-0"><strong>Valor da reserva:</strong> R$ {{ number_format($b->total_amount, 2, ',', '.') }}</p>
                        <a href="{{ route('bookings.show', $b) }}" class="btn btn-sm btn-primary mt-3"
                           onclick="return arenaCrossNav(event, this.href)">
                            <i class="bi bi-info-circle me-1"></i> Ver reserva completa
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Como foi pago</h5>
                        @if ($pagamento)
                            <p class="mb-1"><strong>Forma:</strong> {{ $pagamento->paymentMethod->label ?? '—' }}</p>
                            <p class="mb-1">
                                <strong>Origem:</strong>
                                {{ $pagamento->origin === 'online' ? 'Pagamento online (pelo cliente)' : 'Recebido na arena' }}
                            </p>
                            <p class="mb-1">
                                <strong>Pago em:</strong>
                                {{ optional($pagamento->paid_at)->format('d/m/Y H:i') ?? '—' }}
                            </p>
                            @if ((float) $pagamento->discount_amount > 0)
                                <p class="mb-1">
                                    <strong>Desconto:</strong>
                                    <span class="text-danger">− R$ {{ number_format($pagamento->discount_amount, 2, ',', '.') }}</span>
                                    @if ($pagamento->discount_reason)
                                        <span class="text-muted">({{ $pagamento->discount_reason }})</span>
                                    @endif
                                </p>
                            @endif
                            <p class="mb-0"><strong>Valor pago:</strong> R$ {{ number_format($pagamento->amount, 2, ',', '.') }}</p>
                        @else
                            <p class="text-muted mb-0">Sem pagamento vinculado a este lançamento.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

    </div>

</div>

@endsection

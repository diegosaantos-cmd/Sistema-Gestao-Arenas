@extends('layouts.main')

@section('title', 'Detalhes da reserva')

@section('content')

<div class="container py-4 painel">

    <x-back :href="url()->previous()" history />

    @php
        $statusInfo = [
            'pending'   => ['Pendente',   'bg-warning text-dark'],
            'confirmed' => ['Confirmada', 'bg-success'],
            'completed' => ['Concluída',  'bg-success'],
            'cancelled' => ['Cancelada',  'bg-danger'],
        ];
        $st = $statusInfo[$booking->status] ?? [$booking->status, 'bg-secondary'];
        $emAndamento = $booking->estaEmAndamento();
        $statusRotulo = $emAndamento ? 'Em andamento' : $st[0];
        $dias = [
            0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira',
            3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado',
        ];
    @endphp

    <div class="d-flex align-items-center gap-2 mb-4">
        <h1 class="fw-bold mb-0">Reserva #{{ $numeroReserva ?? $booking->id }}</h1>
        @if ($emAndamento)
            <span class="badge" style="background:#021B35;color:#fff;">Em andamento</span>
        @else
            <span class="badge {{ $st[1] }}">{{ $st[0] }}</span>
        @endif
        @include('partials.payment-badge', ['booking' => $booking])
    </div>

    {{-- Cards em masonry de 2 colunas: equilibra a altura sozinho, seja a
         Reserva (paga) ou o Registro (cancelamento) o card mais alto. --}}
    <div class="reserva-cards">

        {{-- Reserva --}}
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Reserva</h5>
                    <p class="mb-1"><strong>Arena:</strong> {{ $booking->court->arena->name ?? '—' }}</p>
                    {{-- Endereço aqui evita o cliente ter que ir procurar a arena
                         só para saber onde é o jogo. --}}
                    @php $arenaDaReserva = $booking->court?->arena; @endphp
                    @if ($arenaDaReserva)
                        <p class="mb-1">
                            <strong>Endereço:</strong>
                            <i class="bi bi-geo-alt"></i>
                            {{ $arenaDaReserva->address_rua }}, {{ $arenaDaReserva->address_numero }}
                            — {{ $arenaDaReserva->address_bairro }}
                        </p>
                    @endif
                    <p class="mb-1"><strong>Quadra:</strong> {{ $booking->court->name ?? '—' }}</p>
                    <p class="mb-1">
                        <strong>Data:</strong>
                        {{ $dias[$booking->date->dayOfWeek] }}, {{ $booking->date->format('d/m/Y') }}
                    </p>
                    <p class="mb-1">
                        <strong>Horário:</strong>
                        {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
                    </p>
                    <p class="mb-1">
                        <strong>Valor:</strong>
                        R$ {{ number_format($booking->total_amount, 2, ',', '.') }}
                    </p>
                    <p class="mb-0"><strong>Status:</strong> {{ $statusRotulo }}</p>

                    @if (in_array($booking->status, ['confirmed', 'completed']))
                        @php $pago = $booking->payments->firstWhere('status', 'paid'); @endphp
                        <hr>
                        <p class="mb-1">
                            <strong>Pagamento:</strong>
                            @include('partials.payment-badge', ['booking' => $booking])
                        </p>
                        @if ($pago)
                            <p class="mb-1"><strong>Forma:</strong> {{ $pago->paymentMethod->label ?? '—' }}</p>
                            @if ((float) $pago->discount_amount > 0)
                                <p class="mb-1">
                                    <strong>Valor original:</strong>
                                    <span class="text-decoration-line-through text-muted">R$ {{ number_format($booking->total_amount, 2, ',', '.') }}</span>
                                </p>
                                <p class="mb-1">
                                    <strong>Desconto:</strong>
                                    <span class="text-danger">− R$ {{ number_format($pago->discount_amount, 2, ',', '.') }}</span>
                                </p>
                                <p class="mb-1"><strong>Motivo do desconto:</strong> {{ $pago->discount_reason ?: '—' }}</p>
                            @endif
                            <p class="mb-1">
                                <strong>Valor pago:</strong>
                                R$ {{ number_format($pago->amount, 2, ',', '.') }}
                            </p>
                            <p class="mb-0">
                                <strong>Pago em:</strong>
                                {{ optional($pago->paid_at)->format('d/m/Y H:i') ?? '—' }}
                            </p>
                            @if (($podeVerCaixa ?? false) && $pago->cash_register_entry_id)
                                {{-- Sem 'voltar': o back do lançamento cai no destino
                                     padrão (caixa), evitando loop com o back desta tela
                                     (que é url()->previous()). --}}
                                <a href="{{ route('caixa.entry.show', $pago->cash_register_entry_id) }}"
                                   class="btn btn-sm btn-primary mt-3"
                                   onclick="return arenaCrossNav(event, this.href)">
                                    <i class="bi bi-receipt me-1"></i> Ver lançamento completo
                                </a>
                            @endif
                        @endif
                    @endif
                </div>
            </div>

        {{-- Cliente --}}
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Cliente</h5>
                        <p class="mb-1"><strong>Nome:</strong> {{ $booking->nomeCliente() }}</p>
                        <p class="mb-1"><strong>E-mail:</strong> {{ $booking->emailCliente() ?? '—' }}</p>
                        <p class="mb-0"><strong>Telefone:</strong> {{ $booking->telefoneCliente() ?? '—' }}</p>
                    </div>
                </div>

                {{-- Observações --}}
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Observações</h5>
                        <p class="mb-0">{{ $booking->notes ?: '—' }}</p>
                        @if ($registradaPor)
                            <hr>
                            <p class="mb-0">
                                <strong>Registrada por:</strong> {{ $registradaPor }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Registro / cancelamento --}}
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Registro</h5>
                    <p class="mb-1">
                        <strong>Criada em:</strong>
                        {{ optional($booking->created_at)->format('d/m/Y H:i') ?? '—' }}
                    </p>
                    <p class="mb-1">
                        <strong>Atualizada em:</strong>
                        {{ optional($booking->updated_at)->format('d/m/Y H:i') ?? '—' }}
                    </p>

                    @if ($booking->status === 'cancelled')
                        <hr>
                        <h6 class="fw-bold text-danger">Cancelamento</h6>
                        <p class="mb-1">
                            <strong>Cancelada por:</strong> {{ $canceladoPor ?? '—' }}
                        </p>
                        <p class="mb-1">
                            <strong>Quando:</strong>
                            {{ optional($booking->cancelled_at)->format('d/m/Y H:i') ?? '—' }}
                        </p>
                        <p class="mb-1">
                            <strong>Motivo:</strong> {{ $booking->cancellation_reason ?: '—' }}
                        </p>
                        <p class="mb-1">
                            <strong>Taxa:</strong>
                            @if ((float) $booking->cancellation_fee_amount > 0)
                                <span class="text-danger fw-semibold">R$ {{ number_format($booking->cancellation_fee_amount, 2, ',', '.') }}</span>
                            @else
                                <span class="text-muted">Sem taxa</span>
                            @endif
                        </p>
                        @php $estorno = $booking->payments->first(fn ($p) => $p->refunded_at !== null); @endphp
                        @if ($estorno)
                            <p class="mb-1">
                                <strong>Reembolso:</strong>
                                <span class="text-success fw-semibold">R$ {{ number_format($estorno->refund_amount, 2, ',', '.') }}</span>
                                @if (! $estorno->refund_cash_register_entry_id)
                                    <span class="badge bg-warning text-dark ms-1">a lançar no caixa</span>
                                @endif
                            </p>
                            <p class="mb-0 small {{ $estorno->ehDinheiro() ? 'text-danger' : 'text-muted' }}">
                                <i class="bi bi-cash-coin me-1"></i> {{ $estorno->comoReembolsar() }}
                            </p>
                        @endif
                    @endif
                    </div>
                </div>

    </div>

</div>

@endsection

@extends('layouts.main')

@section('title', 'Detalhes da reserva')

@section('content')

<div class="container py-4">

    <a href="{{ url()->previous() }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar
    </a>

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

    <div class="row g-4">

        {{-- Reserva --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Reserva</h5>
                    <p class="mb-1"><strong>Arena:</strong> {{ $booking->court->arena->name ?? '—' }}</p>
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
                            <p class="mb-1">
                                <strong>Valor pago:</strong>
                                R$ {{ number_format($pago->amount, 2, ',', '.') }}
                            </p>
                            <p class="mb-0">
                                <strong>Pago em:</strong>
                                {{ optional($pago->paid_at)->format('d/m/Y H:i') ?? '—' }}
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Cliente --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Cliente</h5>
                    <p class="mb-1"><strong>Nome:</strong> {{ $booking->client->user->name ?? '—' }}</p>
                    <p class="mb-1"><strong>E-mail:</strong> {{ $booking->client->user->email ?? '—' }}</p>
                    <p class="mb-0"><strong>Telefone:</strong> {{ $booking->client->user->phone ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Observações --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Observações</h5>
                    <p class="mb-0">{{ $booking->notes ?: '—' }}</p>
                    @if ($funcionario)
                        <hr>
                        <p class="mb-0">
                            <strong>Registrada por:</strong>
                            {{ $funcionario->user->name ?? 'Funcionário' }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Registro / cancelamento --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
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
                        <p class="mb-0">
                            <strong>Taxa:</strong>
                            @if ((float) $booking->cancellation_fee_amount > 0)
                                <span class="text-danger fw-semibold">R$ {{ number_format($booking->cancellation_fee_amount, 2, ',', '.') }}</span>
                            @else
                                <span class="text-muted">Sem taxa</span>
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>

@endsection

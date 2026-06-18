@extends('layouts.main')

@section('title', $arena->name)

@section('content')

<div class="dashboard-container container-fluid py-4">

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('client.arenas.index') }}" class="btn btn-dark btn-sm">
            ← Voltar para as arenas
        </a>
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title">{{ $arena->name }}</h1>
        <p class="dashboard-subtitle">
            <i class="bi bi-geo-alt me-1"></i>
            {{ $arena->address_rua }}, {{ $arena->address_numero }} - {{ $arena->address_bairro }}
        </p>
    </div>

    <!-- Quadras (destaque para a reserva) -->
    <h2 class="section-title">Quadras</h2>

    <div class="row g-4 mb-5">
        @forelse ($courts as $court)
            <div class="col-md-4">
                <div class="dashboard-box h-100 d-flex flex-column">
                    <h3 class="fw-bold mb-1">{{ $court->name }}</h3>
                    <div class="text-muted mb-3">
                        <div><strong>Valor/hora:</strong> R$ {{ number_format($court->hourly_rate, 2, ',', '.') }}</div>
                        <div>
                            <strong>Esportes:</strong>
                            @forelse ($court->sports as $s)
                                {{ \App\Models\Court::SPORTS[$s->sport] ?? $s->sport }}@if(! $loop->last), @endif
                            @empty
                                —
                            @endforelse
                        </div>
                    </div>

                    <a href="{{ route('client.bookings.create', [$arena, $court]) }}"
                       class="btn dashboard-btn-primary mt-auto">
                        <i class="bi bi-calendar-plus me-2"></i> Reservar
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="dashboard-box text-center text-muted">
                    Esta arena ainda não tem quadras disponíveis.
                </div>
            </div>
        @endforelse
    </div>

    <!-- Dados da arena -->
    <h2 class="section-title">Sobre a arena</h2>

    <div class="row g-4">

        <!-- Contato + Pagamento -->
        <div class="col-lg-6">
            <div class="dashboard-box h-100">
                @if ($arena->description)
                    <p>{{ $arena->description }}</p>
                    <hr>
                @endif

                @php
                    $telefone = $arena->phone ?: optional($arena->owner?->user)->phone;
                    $email = $arena->contact_email ?: optional($arena->owner?->user)->email;
                @endphp

                <h2 class="section-title" style="font-size: 1.4rem;">Contato</h2>
                <p class="mb-1"><strong>Telefone:</strong> {{ $telefone ?: '—' }}</p>
                <p class="mb-3"><strong>E-mail:</strong> {{ $email ?: '—' }}</p>

                <h2 class="section-title" style="font-size: 1.4rem;">Formas de pagamento</h2>
                @forelse ($arena->paymentMethods as $pm)
                    <span class="badge bg-light text-dark border me-1 mb-1">{{ $pm->label }}</span>
                @empty
                    <span class="text-muted">—</span>
                @endforelse
            </div>
        </div>

        <!-- Horário -->
        <div class="col-lg-6">
            <div class="dashboard-box h-100">
                <h2 class="section-title" style="font-size: 1.4rem;">Horário de funcionamento</h2>

                @php
                    $dias = [
                        0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira',
                        3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado',
                    ];
                    $porDia = $arena->businessHours->groupBy('day_of_week');
                @endphp

                <ul class="list-unstyled mb-0">
                    @foreach ($dias as $num => $nome)
                        <li class="d-flex justify-content-between border-bottom py-1">
                            <span>{{ $nome }}</span>
                            <span>
                                @if ($porDia->has($num))
                                    @foreach ($porDia[$num] as $h)
                                        {{ substr($h->opens_at, 0, 5) }}–{{ substr($h->closes_at, 0, 5) }}@if(! $loop->last), @endif
                                    @endforeach
                                @else
                                    <span class="text-muted">Fechado</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

    </div>

</div>

@endsection

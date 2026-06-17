@extends('layouts.main')

@section('title', 'Detalhes da Arena')

@section('content')

<div class="container py-4">

    <a href="{{ route('owners.dashboard') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao painel
    </a>

    <!-- Cabeçalho + ações -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                {{ $arena->name }}
                @if ($arena->active)
                    <span class="badge bg-success align-middle">Ativa</span>
                @else
                    <span class="badge bg-secondary align-middle">Inativa</span>
                @endif
            </h1>
            <button type="button" class="btn btn-sm btn-outline-primary">✏️ Editar nome</button>
        </div>

        {{-- Botões sem ação por enquanto (lógica depois) --}}
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-warning">
                {{ $arena->active ? '🚫 Desativar' : '✅ Reativar' }}
            </button>
            <button type="button" class="btn btn-danger">🗑️ Excluir</button>
        </div>
    </div>

    <div class="row g-4">

        <!-- Endereço + Contato -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Endereço &amp; Contato</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary">✏️ Editar</button>
                    </div>
                    <h6 class="fw-bold">Endereço</h6>
                    <p class="mb-1">{{ $arena->address_rua }}, {{ $arena->address_numero }}</p>
                    <p class="mb-0">{{ $arena->address_bairro }}</p>

                    <hr>

                    <h6 class="fw-bold">Contato</h6>
                    <p class="mb-1"><strong>Telefone:</strong> {{ $arena->phone ?? '—' }}</p>
                    <p class="mb-1"><strong>E-mail:</strong> {{ $arena->contact_email ?? '—' }}</p>
                    @if ($arena->description)
                        <p class="mb-0 mt-2"><strong>Descrição:</strong><br>{{ $arena->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Formas de pagamento -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Formas de Pagamento</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary">✏️ Editar</button>
                    </div>
                    @forelse ($arena->paymentMethods as $pm)
                        <span class="badge bg-light text-dark border me-1 mb-1">{{ $pm->label }}</span>
                    @empty
                        <span class="text-muted">Nenhuma cadastrada</span>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Horário de funcionamento -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Horário de Funcionamento</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary">✏️ Editar</button>
                    </div>

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

        <!-- Quadras -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Quadras ({{ $arena->courts->count() }})</h5>
                        <a href="{{ route('quadras.index') }}" class="btn btn-sm btn-outline-primary">✏️ Editar</a>
                    </div>

                    @forelse ($arena->courts as $court)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ $court->name }}</strong>
                                @if ($court->active)
                                    <span class="badge bg-success">Ativa</span>
                                @else
                                    <span class="badge bg-secondary">Inativa</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                <div><strong>Valor/hora:</strong> R$ {{ number_format($court->hourly_rate, 2, ',', '.') }}</div>
                                <div>
                                    <strong>Esportes:</strong>
                                    @if ($court->sports->isNotEmpty())
                                        @foreach ($court->sports as $s)
                                            {{ \App\Models\Court::SPORTS[$s->sport] ?? $s->sport }}@if(! $loop->last), @endif
                                        @endforeach
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <span class="text-muted">Nenhuma quadra cadastrada</span>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

</div>

@endsection

@extends('layouts.main')

@section('title', 'Reservar - ' . $court->name)

@section('content')

<div class="dashboard-container container-fluid py-4">

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('client.arenas.show', $arena) }}" class="btn btn-dark btn-sm">
            ← Voltar
        </a>
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title">Reservar — {{ $court->name }}</h1>
        <p class="dashboard-subtitle">
            {{ $arena->name }} ·
            R$ {{ number_format($court->hourly_rate, 2, ',', '.') }}/hora
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $diasNome = [
            0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira',
            3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado',
        ];
    @endphp

    <div class="row g-4">

        {{-- Coluna principal: data + horários + dados --}}
        <div class="col-lg-8">

            {{-- Passo 1: escolher a data --}}
            <div class="dashboard-box mb-4">
                <h2 class="section-title" style="font-size: 1.4rem;">1. Escolha o dia</h2>

                <form method="GET" action="{{ route('client.bookings.create', [$arena, $court]) }}"
                      class="d-flex gap-2 flex-wrap">
                    <input type="date" name="date" value="{{ $date }}"
                           min="{{ now()->toDateString() }}"
                           class="form-control" style="max-width: 220px;" required>
                    <button class="btn dashboard-btn-primary px-4">
                        <i class="bi bi-search me-2"></i> Ver horários
                    </button>
                </form>
            </div>

            {{-- Passo 2: horários + dados --}}
            @if (! $aberto)
                @php $d = \Carbon\Carbon::parse($date); @endphp
                <div class="dashboard-box text-center text-danger fw-bold">
                    <i class="bi bi-door-closed me-2"></i>
                    A arena está fechada em {{ $diasNome[$d->dayOfWeek] }}, {{ $d->format('d/m/Y') }}.
                    Escolha outro dia.
                </div>
            @elseif ($slots->isEmpty())
                <div class="dashboard-box text-center text-muted">
                    Não há mais horários disponíveis neste dia. Tente outro dia.
                </div>
            @else
                @php $temLivre = $slots->contains(fn ($s) => ! $s['ocupado']); @endphp

                <form method="POST" action="{{ route('client.bookings.store', [$arena, $court]) }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">

                    <div class="dashboard-box mb-4">
                        @php $dSel = \Carbon\Carbon::parse($date); @endphp
                        <h2 class="section-title mb-1" style="font-size: 1.4rem;">2. Escolha o(s) horário(s)</h2>
                        <p class="fw-bold mb-3" style="color: #021B35;">
                            <i class="bi bi-calendar-event me-1"></i>
                            {{ $diasNome[$dSel->dayOfWeek] }}, {{ $dSel->format('d/m/Y') }}
                        </p>

                        <div class="d-flex flex-wrap gap-3 mb-3 small text-muted">
                            <span><span class="d-inline-block border rounded me-1" style="width:14px;height:14px;vertical-align:middle;background:#fff;"></span> Livre</span>
                            <span><span class="d-inline-block rounded me-1" style="width:14px;height:14px;vertical-align:middle;background:#021B35;"></span> Ocupado</span>
                        </div>

                        @php $marcados = collect(old('horarios', [])); @endphp
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($slots as $slot)
                                @if ($slot['ocupado'])
                                    <span class="rounded px-3 py-2"
                                          style="background:#021B35; color:#fff; cursor:not-allowed; text-decoration:line-through;"
                                          title="Horário já reservado">
                                        {{ $slot['start'] }}–{{ $slot['end'] }}
                                    </span>
                                @else
                                    @php $valor = $slot['start'] . '-' . $slot['end']; @endphp
                                    <label class="border rounded px-3 py-2" style="cursor: pointer;">
                                        <input type="checkbox" name="horarios[]" value="{{ $valor }}"
                                               {{ $marcados->contains($valor) ? 'checked' : '' }}>
                                        {{ $slot['start'] }}–{{ $slot['end'] }}
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    @if ($temLivre)
                        @php $u = auth()->user(); @endphp
                        <div class="dashboard-box">
                            <h2 class="section-title" style="font-size: 1.4rem;">3. Confirme seus dados</h2>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label text-muted mb-0">Nome</label>
                                    <div class="fw-semibold">{{ $u->name }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted mb-0">E-mail</label>
                                    <div class="fw-semibold">{{ $u->email ?: '—' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted mb-0">Telefone</label>
                                    <div class="fw-semibold">{{ $u->phone ?: '—' }}</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted mb-1">Formas de pagamento aceitas</label>
                                    <div>
                                        @forelse ($arena->paymentMethods as $pm)
                                            <span class="badge bg-light text-dark border me-1 mb-1">{{ $pm->label }}</span>
                                        @empty
                                            <span class="text-muted">—</span>
                                        @endforelse
                                    </div>
                                    <small class="text-muted">
                                        Você escolhe como pagar <strong>depois que a reserva for confirmada</strong>.
                                    </small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success mt-4 px-4">
                                <i class="bi bi-check-circle me-2"></i> Confirmar reserva
                            </button>
                        </div>
                    @else
                        <div class="dashboard-box text-center text-muted">
                            Todos os horários deste dia já estão reservados. Escolha outro dia.
                        </div>
                    @endif
                </form>
            @endif

        </div>

        {{-- Lateral: dias de funcionamento --}}
        <div class="col-lg-4">
            <div class="dashboard-box" style="position: sticky; top: 1rem;">
                <h3 class="fw-bold mb-2" style="font-size: 1rem;">Dias de funcionamento</h3>
                @if (empty($diasAbertos))
                    <p class="text-muted mb-0">Esta arena ainda não definiu horários de funcionamento.</p>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach ($diasNome as $num => $nome)
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span>{{ $nome }}</span>
                                <span>
                                    @if (isset($diasAbertos[$num]))
                                        {{ $diasAbertos[$num] }}
                                    @else
                                        <span class="text-muted">Fechado</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>

</div>

@endsection

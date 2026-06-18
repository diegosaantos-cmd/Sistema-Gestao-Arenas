@php
    [$rotulo, $cor] = $badges[$b->status] ?? [$b->status, 'bg-secondary'];
    $regra = $b->regraCancelamentoCliente();
@endphp

<div class="col-md-6 col-lg-4">
    <div class="dashboard-box h-100 d-flex flex-column">

        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <div class="mb-1">
                    <span class="text-muted">Arena:</span>
                    <strong>{{ $b->court->arena->name ?? '—' }}</strong>
                </div>
                <div>
                    <span class="text-muted">Quadra:</span>
                    <strong>{{ $b->court->name ?? '—' }}</strong>
                </div>
            </div>
            <span class="badge {{ $cor }}">{{ $rotulo }}</span>
        </div>

        <div class="mb-1">
            <i class="bi bi-calendar-event me-1"></i>
            {{ $dias[$b->date->dayOfWeek] }}, {{ $b->date->format('d/m/Y') }}
        </div>
        <div class="mb-2">
            <i class="bi bi-clock me-1"></i>
            {{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}
        </div>

        <div class="mb-3">
            <strong>R$ {{ number_format($b->total_amount, 2, ',', '.') }}</strong>
        </div>

        @if ($regra)
            @php
                $msgCancelar = $regra === 'taxa'
                    ? 'Falta menos de 1 hora para o horário, então o cancelamento pode ter taxa. Deseja cancelar mesmo assim?'
                    : 'Tem certeza que deseja cancelar esta reserva?';
            @endphp
            <form method="POST" action="{{ route('client.bookings.cancel', $b) }}" class="mt-auto"
                  onsubmit="return confirm(@js($msgCancelar));">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-circle me-1"></i>
                    {{ $regra === 'taxa' ? 'Cancelar (sujeita a taxa)' : 'Cancelar' }}
                </button>
            </form>
        @endif

    </div>
</div>

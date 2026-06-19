@php
    [$rotulo, $cor] = $badges[$b->status] ?? [$b->status, 'bg-secondary'];
    $regra = $b->regraCancelamentoCliente();
@endphp

<div class="col-sm-6 col-lg-3">
    <div class="dashboard-box h-100 d-flex flex-column" style="padding: 18px;">

        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="small">
                <div>
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

        <div class="small mb-1">
            <i class="bi bi-calendar-event me-1"></i>
            {{ $dias[$b->date->dayOfWeek] }}, {{ $b->date->format('d/m/Y') }}
        </div>
        <div class="small mb-2">
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

            <button type="button" class="btn btn-outline-danger btn-sm mt-auto"
                    data-bs-toggle="modal" data-bs-target="#cancelModal{{ $b->id }}">
                <i class="bi bi-x-circle me-1"></i>
                {{ $regra === 'taxa' ? 'Cancelar (sujeita a taxa)' : 'Cancelar' }}
            </button>

            {{-- Modal de confirmação --}}
            <div class="modal fade" id="cancelModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cancelar reserva</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-1">{{ $msgCancelar }}</p>
                            <p class="text-muted small mb-0">
                                {{ $b->court->arena->name ?? '—' }} ·
                                {{ $b->court->name ?? '—' }} ·
                                {{ $b->date->format('d/m/Y') }}
                                {{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Voltar
                            </button>
                            <form method="POST" action="{{ route('client.bookings.cancel', $b) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-x-circle me-1"></i> Sim, cancelar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

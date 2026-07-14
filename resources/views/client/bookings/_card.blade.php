@php
    [$rotulo, $cor] = $badges[$b->status] ?? [$b->status, 'bg-secondary'];
    $regra = $b->regraCancelamentoCliente();  // null quando já começou
    $emAndamento = $b->estaEmAndamento();     // já começou, ainda não terminou
    $paga = $b->isPaga();                      // já paga -> cancelar reembolsa
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
            <div class="d-flex flex-column align-items-end gap-1">
                @if ($emAndamento)
                    <span class="badge" style="background:#021B35;color:#fff;">Em andamento</span>
                @else
                    <span class="badge {{ $cor }}">{{ $rotulo }}</span>
                @endif
                @include('partials.payment-badge', ['booking' => $b])
            </div>
        </div>

        <div class="small mb-1">
            <i class="bi bi-calendar-event me-1"></i>
            {{ $dias[$b->date->dayOfWeek] }}, {{ $b->date->format('d/m/Y') }}
        </div>
        <div class="small mb-2">
            <i class="bi bi-clock me-1"></i>
            {{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}
        </div>

        <div class="mb-2">
            <strong>R$ {{ number_format($b->total_amount, 2, ',', '.') }}</strong>
        </div>

        @if ($b->status === 'cancelled')
            <div class="small mb-2 {{ (float) $b->cancellation_fee_amount > 0 ? 'text-danger' : 'text-muted' }}">
                <i class="bi bi-x-circle me-1"></i> {{ $b->taxaCancelamentoDescricao() }}
            </div>
        @endif

        <div class="mt-auto d-flex flex-wrap gap-2">
            <a href="{{ route('bookings.show', $b) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-info-circle me-1"></i> Detalhes
            </a>

            @if (in_array($b->status, ['confirmed', 'completed']) && ! $b->isPaga())
                <a href="{{ route('client.bookings.pay', ['booking' => $b, 'origem' => request()->route()->getName()]) }}" class="btn btn-success btn-sm">
                    <i class="bi bi-credit-card me-1"></i> Pagar
                </a>
            @endif

            @if ($regra)
                <a href="{{ route('client.bookings.edit', ['booking' => $b, 'from' => request()->route()->getName()]) }}"
                   class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
            @endif

            @if ($regra === 'taxa' && ! $paga)
                {{-- Não paga, com taxa: paga a taxa online para poder cancelar. --}}
                <a href="{{ route('client.bookings.cancel-pay', $b) }}" class="btn btn-danger btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Cancelar (com taxa)
                </a>
            @elseif ($regra)
                {{-- Livre, ou JÁ PAGA (cancela e reembolsa pago − taxa). --}}
                <button type="button" class="btn btn-danger btn-sm"
                        data-bs-toggle="modal" data-bs-target="#cancelModal{{ $b->id }}">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </button>
            @endif
        </div>

        @if ($regra === 'livre' || $paga)
            {{-- Modal de confirmação (cancelamento sem taxa) --}}
            <div class="modal fade" id="cancelModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('client.bookings.cancel', $b) }}">
                            @csrf
                            @method('PATCH')

                            <div class="modal-header">
                                <h5 class="modal-title">Cancelar reserva</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small mb-3">
                                    {{ $b->court->arena->name ?? '—' }} ·
                                    {{ $b->court->name ?? '—' }} ·
                                    {{ $b->date->format('d/m/Y') }}
                                    {{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}
                                </p>

                                @if ($paga)
                                    @php
                                        $taxaCanc = $regra === 'taxa' ? (float) $b->valorTaxaCancelamento() : 0.0;
                                        $pagoAmount = (float) optional($b->payments->firstWhere('status', 'paid'))->amount;
                                        $reembolso = max(0, round($pagoAmount - $taxaCanc, 2));
                                    @endphp
                                    <p class="mb-3">
                                        Você pagou <strong>R$ {{ number_format($pagoAmount, 2, ',', '.') }}</strong> nesta reserva.
                                        @if ($taxaCanc > 0)
                                            Será retida a taxa de <strong>R$ {{ number_format($taxaCanc, 2, ',', '.') }}</strong> e
                                        @endif
                                        você será reembolsado em
                                        <strong class="text-success">R$ {{ number_format($reembolso, 2, ',', '.') }}</strong>.
                                    </p>
                                @else
                                    <p class="mb-3">
                                        Tem certeza que deseja cancelar esta reserva?
                                        <strong>Sem taxa.</strong>
                                    </p>
                                @endif

                                <label class="form-label">Motivo do cancelamento</label>
                                <textarea name="motivo" class="form-control" rows="3" required
                                          placeholder="Ex.: Não vou poder comparecer."></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Voltar
                                </button>
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-x-circle me-1"></i> Sim, cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

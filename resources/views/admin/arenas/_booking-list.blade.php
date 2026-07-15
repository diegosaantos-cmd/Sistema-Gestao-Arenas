@php
    // Número da reserva na sequência da arena ([id => nº]) — uma query só,
    // em vez de contar por linha. $arena vem do escopo de quem inclui o partial.
    $numerosArena = \App\Models\Booking::numerosNaArena($arena->id);
@endphp
<div class="dashboard-box p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 admin-sticky-table">
            <thead class="table-light sticky-top">
                <tr>
                    <th class="ps-3">Nº</th>
                    <th>Data / horário</th>
                    <th>Quadra</th>
                    <th>Cliente</th>
                    <th>Status</th>
                    <th>Pagamento</th>
                    <th class="text-end">Valor</th>
                    @if ($mostrarTaxa ?? false)
                        <th class="text-end">Taxa</th>
                    @endif
                    <th class="pe-3">Cancelada por</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($listaReservas as $reserva)
                    @php
                        [$statusTexto, $statusClasse] = $statusReservas[$reserva->status]
                            ?? [ucfirst($reserva->status), 'bg-secondary'];
                        $situacaoPagamento = $reserva->situacaoPagamento();
                        [$pagamentoTexto, $pagamentoClasse] = match ($situacaoPagamento) {
                            'pago' => ['Pago', 'text-success'],
                            'a_pagar' => ['A pagar', 'text-warning'],
                            'atrasado' => ['Atrasado', 'text-danger'],
                            default => $reserva->status === 'cancelled'
                                ? ['Cancelado', 'text-danger']
                                : ['Pendente', 'text-warning'],
                        };
                    @endphp
                    <tr>
                        <td class="ps-3 fw-semibold text-nowrap">#{{ $numerosArena[$reserva->id] ?? $reserva->id }}</td>
                        <td class="text-nowrap">
                            <strong>{{ $reserva->date?->format('d/m/Y') }}</strong>
                            <div class="small text-muted">
                                {{ substr($reserva->start_time, 0, 5) }}–{{ substr($reserva->end_time, 0, 5) }}
                            </div>
                        </td>
                        <td>{{ $reserva->courtWithTrashed?->name ?? '—' }}</td>
                        <td>{{ $reserva->client?->user?->name ?? '—' }}</td>
                        <td><span class="badge {{ $statusClasse }}">{{ $statusTexto }}</span></td>
                        <td class="text-nowrap fw-semibold {{ $pagamentoClasse }}">
                            {{ $pagamentoTexto }}
                            @if ($situacaoPagamento === 'pago' && $reserva->payments->firstWhere('status', 'paid')?->paymentMethod?->label)
                                <div class="small text-muted fw-normal">
                                    {{ $reserva->payments->firstWhere('status', 'paid')->paymentMethod->label }}
                                </div>
                            @endif
                        </td>
                        @php
                            // Cancelada NÃO gera receita: o valor cheio aparece riscado
                            // (com ou sem taxa). Se houve taxa, ela aparece na coluna Taxa
                            // como o que realmente foi pago.
                            $reservaCancelada = ($mostrarTaxa ?? false) && $reserva->status === 'cancelled';
                        @endphp
                        <td class="text-end text-nowrap {{ $reservaCancelada ? 'text-muted' : 'fw-bold' }}">
                            <span @if ($reservaCancelada) style="text-decoration: line-through;"
                                  title="Não gerou receita — reserva cancelada" @endif>
                                R$ {{ number_format($reserva->total_amount, 2, ',', '.') }}
                            </span>
                            @php $pagoComDesc = $reserva->payments->firstWhere('status', 'paid'); @endphp
                            @if ($pagoComDesc && (float) $pagoComDesc->discount_amount > 0)
                                <div class="small text-danger fw-normal"
                                     title="Motivo: {{ $pagoComDesc->discount_reason }}">
                                    − R$ {{ number_format($pagoComDesc->discount_amount, 2, ',', '.') }} desc.
                                </div>
                                <div class="small text-muted fw-normal">
                                    pago R$ {{ number_format($pagoComDesc->amount, 2, ',', '.') }}
                                </div>
                            @endif
                        </td>
                        @if ($mostrarTaxa ?? false)
                            <td class="text-end text-nowrap">
                                @if ($reserva->cancellation_fee_amount > 0)
                                    <span class="fw-semibold text-danger" title="Taxa de cancelamento paga">
                                        R$ {{ number_format($reserva->cancellation_fee_amount, 2, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                                @php $estorno = $reserva->payments->first(fn ($p) => $p->refunded_at !== null); @endphp
                                @if ($estorno)
                                    <div class="small text-success" title="Reembolso ao cliente">
                                        reemb. R$ {{ number_format($estorno->refund_amount, 2, ',', '.') }}
                                        @unless ($estorno->refund_cash_register_entry_id)
                                            <span class="text-warning">(a lançar)</span>
                                        @endunless
                                    </div>
                                @endif
                            </td>
                        @endif
                        <td class="pe-3">
                            @if ($reserva->status === 'cancelled')
                                <strong class="d-block small">
                                    {{ $reserva->cancelledBy?->name ?? 'Sistema' }}
                                </strong>
                                @if ($reserva->cancelled_at)
                                    <span class="d-block small text-muted">
                                        {{ $reserva->cancelled_at->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                                @if ($reserva->cancellation_reason)
                                    <span class="d-block small text-muted text-truncate"
                                          style="max-width: 260px;"
                                          title="{{ $reserva->cancellation_reason }}">
                                        {{ $reserva->cancellation_reason }}
                                    </span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($mostrarTaxa ?? false) ? 9 : 8 }}" class="text-center text-muted py-5">
                            Nenhuma reserva encontrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($listaReservas->hasPages())
    <div class="mt-3">
        {{ $listaReservas->links() }}
    </div>
@endif

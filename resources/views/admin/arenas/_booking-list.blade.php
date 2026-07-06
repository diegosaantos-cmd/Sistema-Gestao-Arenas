<div class="dashboard-box p-0 overflow-hidden">
    <div class="table-responsive" style="max-height: 55vh; overflow-y: auto;">
        <table class="table table-hover align-middle mb-0 admin-sticky-table">
            <thead class="table-light sticky-top">
                <tr>
                    <th class="ps-3">Data / horário</th>
                    <th>Quadra</th>
                    <th>Cliente</th>
                    <th>Status</th>
                    <th>Pagamento</th>
                    <th class="text-end">Valor</th>
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
                        <td class="ps-3 text-nowrap">
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
                        <td class="text-end text-nowrap fw-bold">
                            R$ {{ number_format($reserva->total_amount, 2, ',', '.') }}
                        </td>
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
                        <td colspan="7" class="text-center text-muted py-5">
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

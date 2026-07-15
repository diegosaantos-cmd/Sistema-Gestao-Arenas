@extends('layouts.main')

@section('title', $titulo . ' — ' . ($client->user->name ?? ''))

@section('content')

<div class="container py-4 painel">

    <x-back :href="route('clients.show', $client)" />

    <h1 class="fw-bold mb-1">{{ $titulo }}</h1>
    <p class="text-muted">
        {{ $client->user->name ?? '—' }}
        <span class="badge bg-secondary align-middle">{{ $reservas->count() }}</span>
    </p>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data / Horário</th>
                            <th>Quadra</th>
                            <th>Status</th>
                            @if ($tipo !== 'canceladas')
                                <th>Pagamento</th>
                            @endif
                            <th class="text-end">Valor</th>
                            @if ($tipo === 'canceladas')
                                <th class="text-end">Taxa</th>
                            @endif
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservas as $b)
                            <tr>
                                <td class="text-nowrap">
                                    {{ $b->date->format('d/m/Y') }}
                                    {{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}
                                </td>
                                <td>{{ $b->court->name ?? '—' }}</td>
                                <td>
                                    @if ($b->estaEmAndamento())
                                        <span class="badge" style="background:#021B35;color:#fff;">Em andamento</span>
                                    @elseif ($b->status === 'confirmed')
                                        <span class="badge bg-success">Confirmada</span>
                                    @elseif ($b->status === 'pending')
                                        <span class="badge bg-warning text-dark">Pendente</span>
                                    @elseif ($b->status === 'completed')
                                        <span class="badge bg-success">Concluída</span>
                                    @else
                                        <span class="badge bg-danger">Cancelada</span>
                                    @endif
                                </td>
                                @if ($tipo !== 'canceladas')
                                    <td>@include('partials.payment-badge', ['booking' => $b])</td>
                                @endif
                                <td class="text-end text-nowrap">
                                    @if ($tipo === 'canceladas')
                                        {{-- Cancelada não gerou receita da reserva: risca o valor. --}}
                                        <span class="text-decoration-line-through text-muted">R$ {{ number_format($b->total_amount, 2, ',', '.') }}</span>
                                    @else
                                        @php $pagoDesc = $b->payments->firstWhere('status', 'paid'); @endphp
                                        @if ($pagoDesc && (float) $pagoDesc->discount_amount > 0)
                                            {{-- Pago com desconto: destaque no que entrou; valor cheio riscado. --}}
                                            <span class="text-muted text-decoration-line-through">R$ {{ number_format($b->total_amount, 2, ',', '.') }}</span>
                                            <div class="small text-danger">− R$ {{ number_format($pagoDesc->discount_amount, 2, ',', '.') }} desc.</div>
                                            <div class="small">pago R$ {{ number_format($pagoDesc->amount, 2, ',', '.') }}</div>
                                        @else
                                            <span class="{{ $tipo === 'nao-pagas' ? 'fw-bold text-danger' : '' }}">R$ {{ number_format($b->total_amount, 2, ',', '.') }}</span>
                                        @endif
                                    @endif
                                </td>
                                @if ($tipo === 'canceladas')
                                    @php $taxa = (float) $b->cancellation_fee_amount; @endphp
                                    <td class="text-end">
                                        @if ($taxa > 0)
                                            <span class="fw-semibold {{ $b->isPaga() ? 'text-success' : '' }}">
                                                R$ {{ number_format($taxa, 2, ',', '.') }}
                                            </span><br>
                                            @if ($b->isPaga())
                                                <span class="badge bg-success">Paga</span>
                                            @else
                                                <span class="badge bg-warning text-dark">A receber</span>
                                            @endif
                                        @else
                                            <span class="text-muted">Sem taxa</span>
                                        @endif
                                        @php $estorno = $b->payments->first(fn ($p) => $p->refunded_at !== null); @endphp
                                        @if ($estorno)
                                            <div class="small text-success mt-1" title="Reembolso ao cliente">
                                                reemb. R$ {{ number_format($estorno->refund_amount, 2, ',', '.') }}
                                                @unless ($estorno->refund_cash_register_entry_id)
                                                    <span class="text-warning">(a lançar)</span>
                                                @endunless
                                            </div>
                                        @endif
                                    </td>
                                @endif
                                <td class="text-end">
                                    <a href="{{ route('bookings.show', $b) }}" class="btn btn-sm btn-primary">Detalhes</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nenhuma reserva nesta categoria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection

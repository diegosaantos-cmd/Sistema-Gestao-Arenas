@extends('layouts.main')

@section('title', 'Histórico de Agendamentos')

@section('content')

<div class="container py-4 painel">

    <div class="d-flex flex-column align-items-start gap-2 mb-3">
        <x-back :href="route('owners.dashboard')" class="mb-0" />
        <a href="{{ route('bookings.index') }}" class="btn btn-dark btn-sm">
            ← Ver próximos
        </a>
    </div>

    <h1 class="fw-bold mb-4">
        Histórico de Agendamentos
        <span class="badge bg-secondary fs-6 align-middle">{{ $bookings->total() }}</span>
        — {{ $arena->name }}
    </h1>

    <form method="GET" class="mb-4 d-flex gap-2 flex-wrap">
        <select name="campo" class="form-select" style="max-width: 160px;">
            <option value="cliente" @selected(request('campo', 'cliente') === 'cliente')>Cliente</option>
            <option value="quadra" @selected(request('campo') === 'quadra')>Quadra</option>
            <option value="data" @selected(request('campo') === 'data')>Data</option>
        </select>
        <input type="text" name="q" id="busca-input" value="{{ request('q') }}" class="form-control"
               style="max-width: 280px;" placeholder="Buscar...">
        <select name="situacao" class="form-select" style="max-width: 180px;">
            <option value="" @selected(request('situacao', '') === '')>Todas as situações</option>
            <option value="concluidas" @selected(request('situacao') === 'concluidas')>Concluídas</option>
            <option value="canceladas" @selected(request('situacao') === 'canceladas')>Canceladas</option>
            <option value="pagas" @selected(request('situacao') === 'pagas')>Pagas</option>
            <option value="atrasadas" @selected(request('situacao') === 'atrasadas')>Atrasadas</option>
        </select>
        <select name="origem" class="form-select" style="max-width: 170px;">
            <option value="" @selected(request('origem', '') === '')>Toda origem</option>
            <option value="site" @selected(request('origem') === 'site')>Online</option>
            <option value="presencial" @selected(request('origem') === 'presencial')>Na arena</option>
        </select>
        <select name="ordenar" class="form-select" style="max-width: 230px;" title="Ordenar a lista">
            @foreach (\App\Models\Booking::ORDENS as $chave => $rotulo)
                <option value="{{ $chave }}" @selected(($ordenar ?? \App\Models\Booking::ORDEM_PADRAO) === $chave)>{{ $rotulo }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filtrar</button>
        @if (request('q') || request('situacao') || request('origem'))
            <a href="{{ route('bookings.history') }}" class="btn btn-outline-secondary">Limpar</a>
        @endif
    </form>

    <script>
        (function () {
            var campo = document.querySelector('select[name="campo"]');
            var input = document.getElementById('busca-input');
            if (!campo || !input) return;

            function ajustar(limpar) {
                input.type = campo.value === 'data' ? 'date' : 'text';
                input.placeholder = campo.value === 'data' ? '' : 'Buscar...';
                if (limpar) input.value = '';
            }

            ajustar(false);
            campo.addEventListener('change', function () { ajustar(true); });
        })();
    </script>

    @php
        $statusInfo = [
            'pending'   => ['Pendente',   'bg-warning text-dark'],
            'confirmed' => ['Confirmada', 'bg-success'],
            'completed' => ['Concluída',  'bg-success'],
            'cancelled' => ['Cancelada',  'bg-danger'],
        ];
        // Número da reserva na arena (o que o staff vê), em lote para evitar N+1.
        $numerosArena = \App\Models\Booking::numerosNaArena($arena->id);
    @endphp

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nº</th>
                        <th>Cliente</th>
                        <th>Origem</th>
                        <th>Quadra</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Pagamento</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="fw-semibold text-nowrap">#{{ $numerosArena[$booking->id] ?? $booking->id }}</td>
                            <td>{{ $booking->nomeCliente() }}</td>
                            <td>@include('partials.origin-badge', ['booking' => $booking])</td>
                            <td>{{ $booking->court->name }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($booking->date)->format('d/m/Y') }}
                                {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
                            </td>
                            <td>
                                @php $st = $statusInfo[$booking->status] ?? [$booking->status, 'bg-secondary']; @endphp
                                <span class="badge {{ $st[1] }} text-center" style="min-width: 100px;">{{ $st[0] }}</span>
                                @if ($booking->status === 'cancelled')
                                    <div class="small mt-1 {{ (float) $booking->cancellation_fee_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ $booking->taxaCancelamentoDescricao() }}
                                    </div>
                                    @php $estorno = $booking->payments->first(fn ($p) => $p->refunded_at !== null); @endphp
                                    @if ($estorno)
                                        <div class="small text-success" title="Reembolso ao cliente">
                                            reembolso R$ {{ number_format($estorno->refund_amount, 2, ',', '.') }}
                                            @unless ($estorno->refund_cash_register_entry_id)
                                                <span class="text-warning">(a lançar)</span>
                                            @endunless
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @include('partials.payment-badge', ['booking' => $booking])
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('bookings.show', $booking) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-info-circle me-1"></i> Detalhes
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                @if (request('q') || request('situacao'))
                                    Nenhum agendamento encontrado para o filtro.
                                @else
                                    Nenhum agendamento no histórico ainda
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>

            @if ($bookings->hasPages())
                <div class="mt-3">{{ $bookings->links() }}</div>
            @endif
        </div>
    </div>

</div>

@endsection

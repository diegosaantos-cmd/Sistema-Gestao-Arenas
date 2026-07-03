@extends('layouts.main')

@section('title', 'Agendamentos da Arena')

@section('content')

<div class="container py-4">

    <div class="d-flex flex-column align-items-start gap-2 mb-3">
        <a href="{{ route('owners.dashboard') }}" class="btn btn-dark btn-sm">
            ← Voltar ao painel
        </a>
        <a href="{{ route('bookings.history') }}" class="btn btn-dark btn-sm">
            ← Ver histórico
        </a>
    </div>

    <h1 class="fw-bold mb-4">
        Próximos Agendamentos
        <span class="badge bg-secondary fs-6 align-middle">{{ $bookings->count() }}</span>
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
        <button class="btn btn-primary">Buscar</button>
        @if (request('q'))
            <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">Limpar</a>
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

            ajustar(false);                                    // estado inicial (mantém valor)
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
    @endphp

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Cliente</th>
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
                            <td>{{ $booking->client->user->name }}</td>
                            <td>{{ $booking->court->name }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($booking->date)->format('d/m/Y') }}
                                {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
                            </td>
                            <td>
                                @php $st = $statusInfo[$booking->status] ?? [$booking->status, 'bg-secondary']; @endphp
                                <span class="badge {{ $st[1] }} text-center" style="min-width: 100px;">{{ $st[0] }}</span>
                            </td>
                            <td>
                                @include('partials.payment-badge', ['booking' => $booking])
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('bookings.show', $booking) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-info-circle me-1"></i> Detalhes
                                </a>
                                <a href="{{ route('bookings.schedule.edit', $booking) }}"
                                   class="btn btn-sm btn-warning">
                                    ✏️ Editar agendamento
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        data-bs-toggle="modal" data-bs-target="#cancelarProximo{{ $booking->id }}">
                                    🚫 Cancelar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Nenhum agendamento por enquanto
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

</div>

{{-- Modais de cancelamento (um por reserva) --}}
@foreach ($bookings as $booking)
    <div class="modal fade" id="cancelarProximo{{ $booking->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Cancelar reserva</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            {{ $booking->client->user->name ?? '—' }} ·
                            {{ $booking->court->name ?? '—' }} ·
                            {{ $booking->date->format('d/m/Y') }}
                            {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
                        </p>
                        <label class="form-label">Motivo do cancelamento</label>
                        <textarea name="motivo" class="form-control" rows="3" required
                                  placeholder="Ex.: Quadra indisponível neste horário."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">🚫 Sim, cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endsection

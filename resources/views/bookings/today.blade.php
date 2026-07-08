@extends('layouts.main')

@section('title', 'Reservas de Hoje')

@section('content')

<div class="container py-4">

    <a href="{{ route('owners.dashboard') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao painel
    </a>

    <h1 class="fw-bold mb-4">
        Reservas de Hoje
        <span class="badge bg-secondary fs-6 align-middle">{{ $bookings->count() }}</span>
        — {{ $arena->name }}
    </h1>

    <p class="text-muted">
        Apenas reservas <strong>confirmadas</strong> ({{ now()->format('d/m/Y') }}).
    </p>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Horário</th>
                        <th>Cliente</th>
                        <th>Quadra</th>
                        <th>Status</th>
                        <th>Pagamento</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="text-nowrap">
                                {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
                            </td>
                            <td>{{ $booking->client->user->name }}</td>
                            <td>{{ $booking->court->name }}</td>
                            <td>
                                @if ($booking->estaEmAndamento())
                                    <span class="badge text-center" style="min-width: 100px; background:#021B35; color:#fff;">Em andamento</span>
                                @else
                                    <span class="badge bg-success text-center" style="min-width: 100px;">Confirmada</span>
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
                                <a href="{{ route('bookings.schedule.edit', $booking) }}"
                                   class="btn btn-sm btn-warning">
                                    ✏️ Editar agendamento
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        data-bs-toggle="modal" data-bs-target="#cancelarHoje{{ $booking->id }}">
                                    🚫 Cancelar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Nenhuma reserva confirmada para hoje.
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
    <div class="modal fade" id="cancelarHoje{{ $booking->id }}" tabindex="-1" aria-hidden="true">
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

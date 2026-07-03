@extends('layouts.main')

@section('title', 'Aguardando confirmação')

@section('content')

<div class="container py-4">

    <div class="d-flex flex-column align-items-start gap-2 mb-3">
        <a href="{{ route('owners.dashboard') }}" class="btn btn-dark btn-sm">
            ← Voltar ao painel
        </a>
    </div>

    <h1 class="fw-bold mb-1">
        Aguardando confirmação
        <span class="badge bg-warning text-dark fs-6 align-middle">{{ $bookings->count() }}</span>
        — {{ $arena->name }}
    </h1>

    <div class="alert alert-info">
        Confirme ou cancele as reservas abaixo. Se você <strong>não fizer nada</strong>,
        elas são <strong>confirmadas automaticamente</strong> em 10 minutos.
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Quadra</th>
                            <th>Data / Horário</th>
                            <th>Confirma auto. em</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            <tr>
                                <td>{{ $booking->client->user->name ?? '—' }}</td>
                                <td>{{ $booking->court->name ?? '—' }}</td>
                                <td>
                                    {{ $booking->date->format('d/m/Y') }}
                                    {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
                                </td>
                                <td class="text-nowrap">
                                    {{ $booking->prazoConfirmacao()->format('d/m H:i') }}
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('bookings.show', $booking) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-info-circle me-1"></i> Detalhes
                                    </a>
                                    <form method="POST" action="{{ route('bookings.confirm', $booking) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success">
                                            ✅ Confirmar
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal" data-bs-target="#cancelarPendente{{ $booking->id }}">
                                        🚫 Cancelar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Nenhuma reserva aguardando confirmação.
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
    <div class="modal fade" id="cancelarPendente{{ $booking->id }}" tabindex="-1" aria-hidden="true">
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
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
                                <span class="badge bg-success text-center" style="min-width: 100px;">Confirmada</span>
                            </td>
                            <td class="text-end text-nowrap">
                                {{-- Sem ação por enquanto (lógica depois) --}}
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-booking-id="{{ $booking->id }}">
                                    ✏️ Editar agendamento
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        data-booking-id="{{ $booking->id }}">
                                    🚫 Cancelar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">
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

@endsection

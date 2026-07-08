@extends('layouts.main')

@section('title', $titulo . ' — ' . ($client->user->name ?? ''))

@section('content')

<div class="container py-4">

    <a href="{{ route('clients.show', $client) }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao cliente
    </a>

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
                            <th>Pagamento</th>
                            <th class="text-end">Valor</th>
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
                                        <span class="badge bg-secondary">Concluída</span>
                                    @else
                                        <span class="badge bg-danger">Cancelada</span>
                                    @endif
                                </td>
                                <td>@include('partials.payment-badge', ['booking' => $b])</td>
                                <td class="text-end {{ $tipo === 'nao-pagas' ? 'fw-bold text-danger' : '' }}">
                                    R$ {{ number_format($b->total_amount, 2, ',', '.') }}
                                </td>
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

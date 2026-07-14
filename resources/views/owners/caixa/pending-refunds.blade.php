@extends('layouts.main')

@section('title', 'Reembolsos a lançar')

@section('content')

<div class="container py-4 painel">

    <x-back :href="route('caixa.index')" />

    <h1 class="fw-bold mb-1">Reembolsos a lançar</h1>
    <p class="text-muted">
        Reembolsos de cancelamentos feitos com o caixa fechado. São <strong>saídas</strong> —
        lance-os no caixa aberto.
    </p>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nº</th>
                            <th>Cliente</th>
                            <th>Quadra / Data</th>
                            <th>Estornado em</th>
                            <th class="text-end">Reembolso (saída)</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reembolsos as $r)
                            <tr>
                                <td class="fw-semibold text-nowrap">#{{ $numerosReservas[$r->booking_id] ?? $r->booking_id }}</td>
                                <td>{{ $r->booking->nomeCliente() }}</td>
                                <td class="text-nowrap">
                                    {{ $r->booking->court->name ?? '—' }} —
                                    {{ optional($r->booking->date)->format('d/m/Y') }}
                                    {{ substr($r->booking->start_time, 0, 5) }}–{{ substr($r->booking->end_time, 0, 5) }}
                                </td>
                                <td class="text-nowrap">{{ optional($r->refunded_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-end fw-semibold text-danger">− R$ {{ number_format($r->refund_amount, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('caixa.launch-refund', $r) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">📤 Lançar saída</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Nenhum reembolso pendente de lançamento.
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

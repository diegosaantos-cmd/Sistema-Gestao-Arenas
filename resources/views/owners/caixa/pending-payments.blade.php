@extends('layouts.main')

@section('title', 'Pagamentos a lançar')

@section('content')

<div class="container py-4">

    <a href="{{ route('caixa.index') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao caixa
    </a>

    <h1 class="fw-bold mb-1">Pagamentos a lançar</h1>
    <p class="text-muted">
        Pagamentos que o cliente fez online com o caixa fechado. Lance-os no caixa aberto.
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
                            <th>Cliente</th>
                            <th>Quadra / Data</th>
                            <th>Forma</th>
                            <th>Pago em</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pagamentos as $p)
                            <tr>
                                <td>{{ $p->booking->client->user->name ?? '—' }}</td>
                                <td class="text-nowrap">
                                    {{ $p->booking->court->name ?? '—' }} —
                                    {{ optional($p->booking->date)->format('d/m/Y') }}
                                    {{ substr($p->booking->start_time, 0, 5) }}–{{ substr($p->booking->end_time, 0, 5) }}
                                </td>
                                <td>{{ $p->paymentMethod->label ?? '—' }}</td>
                                <td class="text-nowrap">{{ optional($p->paid_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-end fw-semibold text-success">R$ {{ number_format($p->amount, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('caixa.launch-payment', $p) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">📥 Lançar no caixa</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Nenhum pagamento pendente de lançamento.
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

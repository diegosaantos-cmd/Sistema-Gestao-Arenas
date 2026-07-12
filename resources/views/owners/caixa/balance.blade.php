@extends('layouts.main')

@section('title', 'Balanço financeiro')

@section('content')

<div class="container py-4 painel">

    <x-back :href="route('owners.dashboard')" />

    <h1 class="fw-bold mb-1">Balanço financeiro</h1>
    <p class="text-muted">Total gerado pela arena <strong>{{ $arena->name }}</strong> (todo o período).</p>

    {{-- Totais gerais --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100 text-center">
                <div class="card-body">
                    <h6 class="text-secondary mb-1">Total de entradas</h6>
                    <h2 class="fw-bold text-success mb-0">+ R$ {{ number_format($totalEntradas, 2, ',', '.') }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100 text-center">
                <div class="card-body">
                    <h6 class="text-secondary mb-1">Total de saídas</h6>
                    <h2 class="fw-bold text-danger mb-0">− R$ {{ number_format($totalSaidas, 2, ',', '.') }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100 text-center bg-light">
                <div class="card-body">
                    <h6 class="text-secondary mb-1">Lucro total</h6>
                    <h2 class="fw-bold mb-0 {{ $totalLucro >= 0 ? 'text-success' : 'text-danger' }}">
                        R$ {{ number_format($totalLucro, 2, ',', '.') }}
                    </h2>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumo mês a mês --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Por mês</h5>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th class="text-end">Entradas</th>
                            <th class="text-end">Saídas</th>
                            <th class="text-end">Lucro</th>
                            <th class="text-end">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($porMes as $linha)
                            <tr>
                                <td class="fw-semibold">{{ $linha['label'] }}</td>
                                <td class="text-end text-success">+ R$ {{ number_format($linha['entradas'], 2, ',', '.') }}</td>
                                <td class="text-end text-danger">− R$ {{ number_format($linha['saidas'], 2, ',', '.') }}</td>
                                <td class="text-end fw-semibold {{ $linha['lucro'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    R$ {{ number_format($linha['lucro'], 2, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('caixa.report', ['mes' => $linha['valor'], 'from' => 'balanco']) }}"
                                       class="btn btn-outline-dark btn-sm">
                                        Ver mês
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Nenhum lançamento no caixa ainda.
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
@extends('layouts.main')

@section('title', 'Clientes da Arena')

@section('content')

<div class="container py-4">

    <a href="{{ route('owners.dashboard') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao painel
    </a>

    <h1 class="fw-bold mb-4">Clientes — {{ $arena->name }}</h1>

    <form method="GET" class="mb-4 d-flex gap-2 flex-wrap" style="max-width: 480px;">
        <input type="text" name="q" value="{{ request('q') }}"
               class="form-control" placeholder="Buscar por nome ou e-mail...">
        <button class="btn btn-primary">Buscar</button>
        @if (request('q'))
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Limpar</a>
        @endif
    </form>

    @forelse ($clients as $client)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">

                <div>
                    <h5 class="fw-bold mb-1">{{ $client->user->name }}</h5>
                    <div class="text-muted small">
                        <div><strong>E-mail:</strong> {{ $client->user->email }}</div>
                        <div><strong>Telefone:</strong> {{ $client->user->phone ?? '—' }}</div>
                    </div>
                </div>

                <span class="badge bg-primary fs-6">
                    {{ $reservasPorCliente[$client->id] ?? 0 }} reserva(s)
                </span>

            </div>
        </div>
    @empty
        <div class="alert alert-light border text-center text-muted">
            Nenhum cliente ainda — ninguém fez reserva nesta arena.
        </div>
    @endforelse

</div>

@endsection

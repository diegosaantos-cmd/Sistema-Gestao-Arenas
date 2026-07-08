@extends('layouts.main')

@section('title', 'Cliente — ' . ($client->user->name ?? ''))

@section('content')

<div class="container py-4">

    <a href="{{ route('clients.index') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar aos clientes
    </a>

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

    {{-- Cabeçalho: dados do cliente --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h1 class="fw-bold mb-2">{{ $client->user->name ?? '—' }}</h1>
            <div class="text-muted">
                <div><strong>E-mail:</strong> {{ $client->user->email ?? '—' }}</div>
                <div><strong>Telefone:</strong> {{ $client->user->phone ?? '—' }}</div>
                @if ($canceladasCount)
                    <div class="mt-1"><strong>Canceladas:</strong> {{ $canceladasCount }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Cards de reservas (clique para ver a lista completa) --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <a href="{{ route('clients.bookings', [$client, 'a-realizar']) }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 text-center card-hover">
                    <div class="card-body">
                        <div class="display-6 fw-bold text-primary">{{ $proximas->count() }}</div>
                        <div class="text-muted">Reservas a realizar</div>
                        <div class="small text-primary mt-2">Ver lista →</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="{{ route('clients.bookings', [$client, 'realizadas']) }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 text-center card-hover">
                    <div class="card-body">
                        <div class="display-6 fw-bold">{{ $realizadas->count() }}</div>
                        <div class="text-muted">Reservas realizadas</div>
                        <div class="small text-primary mt-2">Ver lista →</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="{{ route('clients.bookings', [$client, 'nao-pagas']) }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 text-center card-hover {{ $naoPagas->count() ? 'border-danger' : '' }}"
                     @if ($naoPagas->count()) style="border-width:2px !important;" @endif>
                    <div class="card-body">
                        <div class="display-6 fw-bold {{ $naoPagas->count() ? 'text-danger' : 'text-success' }}">
                            {{ $naoPagas->count() }}
                        </div>
                        <div class="text-muted">Reservas não pagas</div>
                        <div class="small text-primary mt-2">Ver lista →</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Enviar mensagem ao cliente --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 fw-bold mb-1">Enviar mensagem</h2>
                <p class="text-muted small mb-0">
                    A mensagem chega ao cliente nas notificações dele dentro do sistema e no e-mail.
                </p>
            </div>
            <a href="{{ route('clients.message.create', $client) }}" class="btn btn-success">
                <i class="bi bi-send me-1"></i> Enviar mensagem
            </a>
        </div>
    </div>

</div>

@endsection

@extends('layouts.main')

@section('title', 'Clientes da Arena')

@section('content')

<div class="container py-4">

    <a href="{{ route('owners.dashboard') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao painel
    </a>

    <h1 class="fw-bold mb-4">Clientes — {{ $arena->name }}</h1>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <div class="d-flex gap-1 align-items-center">
                <input type="text" name="q" value="{{ request('q') }}"
                       class="form-control" style="max-width: 240px;"
                       placeholder="Buscar cliente por nome ou e-mail...">
                @if (request('q'))
                    <a href="{{ route('clients.index', array_filter(['filtro' => request('filtro')])) }}"
                       class="btn btn-outline-secondary" title="Limpar busca">Limpar</a>
                @endif
            </div>
            <div class="d-flex gap-1 align-items-center">
                <select name="filtro" class="form-select" style="max-width: 220px;">
                    <option value="" @selected(request('filtro', '') === '')>Todos os clientes</option>
                    <option value="atrasados" @selected(request('filtro') === 'atrasados')>Com pagamentos atrasados</option>
                    <option value="pendentes" @selected(request('filtro') === 'pendentes')>Com pagamentos pendentes</option>
                </select>
                @if (request('filtro'))
                    <a href="{{ route('clients.index', array_filter(['q' => request('q')])) }}"
                       class="btn btn-outline-secondary" title="Limpar filtro">Limpar</a>
                @endif
            </div>
            <button class="btn btn-primary">Filtrar</button>
        </form>

        <a href="{{ route('clients.broadcast.create') }}" class="btn btn-success">
            <i class="bi bi-megaphone me-1"></i> Disparar mensagem para vários
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th class="text-center">Res. realizadas</th>
                            <th class="text-center">Pag. pendentes</th>
                            <th class="text-center">Pag. atrasados</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            @php $s = $stats[$client->id] ?? ['realizadas' => 0, 'pendentes' => 0, 'atrasados' => 0]; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $client->user->name }}</td>
                                <td>{{ $client->user->email }}</td>
                                <td>{{ $client->user->phone ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success">{{ $s['realizadas'] }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($s['pendentes'] > 0)
                                        <span class="badge bg-warning text-dark">{{ $s['pendentes'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($s['atrasados'] > 0)
                                        <span class="badge bg-danger">{{ $s['atrasados'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-info-circle me-1"></i> Detalhes
                                    </a>
                                    <a href="{{ route('clients.message.create', $client) }}" class="btn btn-sm btn-success">
                                        <i class="bi bi-send me-1"></i> Enviar mensagem
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    @if ($filtro === 'atrasados')
                                        Nenhum cliente com pagamentos atrasados.
                                    @elseif ($filtro === 'pendentes')
                                        Nenhum cliente com pagamentos pendentes.
                                    @elseif (request('q'))
                                        Nenhum cliente encontrado para a busca.
                                    @else
                                        Nenhum cliente ainda — ninguém fez reserva nesta arena.
                                    @endif
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

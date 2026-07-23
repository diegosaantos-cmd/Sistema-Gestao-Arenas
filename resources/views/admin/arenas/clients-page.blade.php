@extends('layouts.main')

@section('title', 'Clientes — ' . $arena->name)

@section('content')
<div class="dashboard-container container-fluid py-4">
    <x-back :href="route('admin.arenas.show', [$arena, 'origem' => request('origem')])" />

    <div class="mb-4">
        <div class="text-muted text-uppercase fw-semibold small" style="letter-spacing:.05em;">Arena {{ $arena->name }}</div>
        <h1 class="dashboard-title mb-1">Clientes da arena</h1>
    </div>

    <form method="GET" class="mb-4 d-flex gap-2 flex-wrap" style="max-width: 520px;">
        <input type="hidden" name="origem" value="{{ request('origem') }}">
        <input type="text" name="busca_cliente" value="{{ request('busca_cliente') }}"
               class="form-control" placeholder="Nome, e-mail ou telefone…">
        <button class="btn btn-primary">Buscar</button>
        @if (request('busca_cliente'))
            <a href="{{ route('admin.arenas.clients.page', [$arena, 'origem' => request('origem')]) }}" class="btn btn-outline-secondary">Limpar</a>
        @endif
    </form>

    <div class="dashboard-box p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 small" style="min-width: 720px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Cliente</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Nascimento</th>
                        <th>Cadastro</th>
                        <th class="text-end">Reservas</th>
                        <th class="text-end pe-3">Total gasto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $cliente)
                        <tr>
                            <td class="ps-3 fw-bold text-break">{{ $cliente->user?->name ?? '—' }}</td>
                            <td class="text-break" style="overflow-wrap: anywhere;">{{ $cliente->user?->email ?? '—' }}</td>
                            <td>{{ $cliente->user?->phone ?: '—' }}</td>
                            <td>{{ $cliente->date_of_birth ? \Carbon\Carbon::parse($cliente->date_of_birth)->format('d/m/Y') : '—' }}</td>
                            <td>{{ optional($cliente->user?->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end">{{ $cliente->reservas_total }}</td>
                            <td class="text-end pe-3">R$ {{ number_format($cliente->valor_total, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum cliente encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $clientes->links() }}</div>
</div>
@endsection

@extends('layouts.main')

@section('title', 'Quadras da Arena')

@section('content')

<div class="container py-4 painel">

    @if (request('from') === 'arena')
        <x-back :href="route('arenas.show', $arena->id)" />
    @else
        <x-back :href="route('owners.dashboard')" />
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="fw-bold mb-0">Quadras — {{ $arena->name }}</h1>
        <a href="{{ route('quadras.create') }}" class="btn btn-primary">
            + Nova Quadra
        </a>
    </div>

    @forelse ($courts as $court)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">

                <div>
                    <h5 class="fw-bold mb-1">
                        {{ $court->name }}
                        @if ($court->active)
                            <span class="badge bg-success align-middle">Ativa</span>
                        @else
                            <span class="badge bg-secondary align-middle">Inativa</span>
                        @endif
                    </h5>
                    <div class="text-muted small">
                        <div><strong>Valor/hora:</strong> R$ {{ number_format($court->hourly_rate, 2, ',', '.') }}</div>
                        <div>
                            <strong>Esportes:</strong>
                            @if ($court->sports->isNotEmpty())
                                @foreach ($court->sports as $s)
                                    {{ \App\Models\Court::SPORTS[$s->sport] ?? $s->sport }}@if(! $loop->last), @endif
                                @endforeach
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('quadras.edit', $court) }}" class="btn btn-sm btn-warning">✏️ Editar</a>
                    @if ($court->active)
                        <button type="button" class="btn btn-sm btn-secondary"
                                data-bs-toggle="modal" data-bs-target="#desativarQuadraModal{{ $court->id }}">
                            🚫 Desativar
                        </button>
                    @else
                        <form method="POST" action="{{ route('quadras.toggle', $court) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-success">✅ Reativar</button>
                        </form>
                    @endif
                </div>

            </div>
        </div>

        @if ($court->active)
            {{-- Modal de confirmação de desativação --}}
            <div class="modal fade" id="desativarQuadraModal{{ $court->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Desativar quadra</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            Ao desativar, a quadra <strong>{{ $court->name }}</strong>
                            <strong>deixa de aparecer para novos agendamentos</strong>.
                            Se houver reservas futuras, você poderá informar o motivo do
                            cancelamento na próxima tela.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Voltar
                            </button>
                            <form method="POST" action="{{ route('quadras.toggle', $court) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-danger">
                                    🚫 Sim, desativar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @empty
        <div class="alert alert-light border text-center text-muted">
            Nenhuma quadra cadastrada nesta arena.
        </div>
    @endforelse

</div>

@endsection

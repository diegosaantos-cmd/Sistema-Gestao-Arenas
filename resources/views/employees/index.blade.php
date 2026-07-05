@extends('layouts.main')

@section('title', 'Funcionários da Arena')

@section('content')

<div class="container py-4">

    <a href="{{ route('owners.dashboard') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao painel
    </a>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="fw-bold mb-0">Funcionários — {{ $arena->name }}</h1>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            + Novo Funcionário
        </a>
    </div>

    @forelse ($employees as $employee)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">

                <div>
                    <h5 class="fw-bold mb-1">
                        {{ $employee->user->name }}
                        @if ($employee->active)
                            <span class="badge bg-success align-middle">Ativo</span>
                        @else
                            <span class="badge bg-secondary align-middle">Inativo</span>
                        @endif
                    </h5>
                    <div class="text-muted small">
                        <div>
                            <strong>Tipo:</strong>
                            {{ $employee->access_level === 'managerial' ? 'Administrador' : 'Funcionário' }}
                            · <strong>Cargo:</strong> {{ $employee->position }}
                        </div>
                        <div><strong>E-mail:</strong> {{ $employee->user->email }}</div>
                        <div><strong>Telefone:</strong> {{ $employee->user->phone ?? '—' }}</div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    {{-- Editar perfil: sem ação por enquanto (lógica depois) --}}
                    <button type="button" class="btn btn-sm btn-warning">✏️ Editar perfil</button>

                    <form action="{{ route('employees.toggle', $employee) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm {{ $employee->active ? 'btn-secondary' : 'btn-success' }}">
                            {{ $employee->active ? '🚫 Desativar perfil' : '✅ Ativar perfil' }}
                        </button>
                    </form>

                    <button type="button" class="btn btn-sm btn-danger"
                            data-bs-toggle="modal" data-bs-target="#excluirFuncionario{{ $employee->id }}">
                        🗑️ Excluir perfil
                    </button>
                </div>

            </div>
        </div>

        {{-- Modal de confirmação de exclusão --}}
        <div class="modal fade" id="excluirFuncionario{{ $employee->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('employees.destroy', $employee) }}">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title text-danger">Excluir perfil</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                Deseja realmente excluir o perfil de
                                <strong>{{ $employee->user->name }}</strong>?
                            </div>
                            O funcionário deixa de aparecer e perde o acesso ao sistema.
                            O <strong>histórico de agendamentos</strong> que ele registrou
                            <strong>é preservado</strong>.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">🗑️ Sim, excluir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-light border text-center text-muted">
            Nenhum funcionário cadastrado nesta arena.
        </div>
    @endforelse

</div>

@endsection

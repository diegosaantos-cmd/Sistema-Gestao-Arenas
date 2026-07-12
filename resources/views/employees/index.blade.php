@extends('layouts.main')

@section('title', 'Funcionários da Arena')

@section('content')

<div class="container py-4 painel">

    <x-back :href="route('owners.dashboard')" />

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="fw-bold mb-0">Funcionários — {{ $arena->name }}</h1>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            + Novo Funcionário
        </a>
    </div>

    @php $ehGerente = \App\Support\ArenaAtual::ehGerente(); @endphp

    @forelse ($employees as $employee)
        @php
            // O gerente só age sobre atendentes (basic); nunca sobre gerentes
            // (nem sobre si mesmo). Espelha EmployeeController::guardEmployee.
            $podeAgir = ! $ehGerente || $employee->access_level !== 'managerial';
        @endphp
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">

                <div>
                    <h5 class="fw-bold mb-1">
                        {{ $employee->user->name }}
                        @if ($employee->user_id === auth()->id())
                            <span class="badge bg-info text-dark align-middle">Você</span>
                        @endif
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

                @if ($podeAgir)
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-warning">✏️ Editar perfil</a>

                        @if ($employee->active)
                            <button type="button" class="btn btn-sm btn-secondary"
                                    data-bs-toggle="modal" data-bs-target="#desativarFuncionario{{ $employee->id }}">
                                🚫 Desativar perfil
                            </button>
                        @else
                            <form action="{{ route('employees.toggle', $employee) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success">✅ Ativar perfil</button>
                            </form>
                        @endif

                        <button type="button" class="btn btn-sm btn-danger"
                                data-bs-toggle="modal" data-bs-target="#excluirFuncionario{{ $employee->id }}">
                            🗑️ Excluir perfil
                        </button>
                    </div>
                @else
                    <span class="text-muted fst-italic">
                        <i class="bi bi-lock me-1"></i> Não tem permissão para ações
                    </span>
                @endif

            </div>
        </div>

        @if ($podeAgir)
        {{-- Modal de confirmação de desativação --}}
        @if ($employee->active)
            <div class="modal fade" id="desativarFuncionario{{ $employee->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('employees.toggle', $employee) }}">
                            @csrf
                            @method('PATCH')
                            <div class="modal-header">
                                <h5 class="modal-title">Desativar perfil</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                Deseja realmente desativar o perfil de
                                <strong>{{ $employee->user->name }}</strong>?
                                Ele perde o acesso ao sistema até ser reativado, mas o
                                <strong>histórico é preservado</strong>.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger">🚫 Sim, desativar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

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
        @endif
    @empty
        <div class="alert alert-light border text-center text-muted">
            Nenhum funcionário cadastrado nesta arena.
        </div>
    @endforelse

</div>

@endsection

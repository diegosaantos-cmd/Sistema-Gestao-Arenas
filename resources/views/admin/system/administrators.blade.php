@extends('layouts.main')

@section('title', 'Administradores do Sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <x-back :href="route('admin.dashboard')" />

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Administradores do sistema</h1>
        <p class="dashboard-subtitle mb-0">Consulte e gerencie os administradores gerais.</p>
    </div>


    <div class="dashboard-box p-0 overflow-hidden system-admins-box">
        <div class="table-responsive system-admins-container" style="max-height: 72vh; overflow-y: auto; padding-bottom: 18px;">
            <table class="table table-sm table-hover align-middle mb-0 small system-admins-table admin-sticky-table"
                   style="min-width: 920px;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3">Administrador</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Cadastro</th>
                        <th>Situação</th>
                        <th class="text-end pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody data-admins-body>
                    @forelse ($administradores as $administrador)
                        <tr data-admin-row
                            data-admin-search="{{ $administrador->name }} {{ $administrador->email }} {{ $administrador->phone }}">
                            <td class="ps-3 fw-bold text-break">
                                {{ $administrador->name }}
                                @if ($administrador->is(auth()->user()))
                                    <span class="badge bg-primary d-block mt-1" style="width: fit-content;">Você</span>
                                @endif
                            </td>
                            <td class="text-break" style="overflow-wrap: anywhere;">{{ $administrador->email }}</td>
                            <td class="admin-secondary-column text-break">{{ $administrador->phone ?: '—' }}</td>
                            <td class="admin-secondary-column">
                                @if ($administrador->created_at)
                                    <span class="d-block">{{ $administrador->created_at->format('d/m/Y') }}</span>
                                    <span class="d-block text-muted">{{ $administrador->created_at->format('H:i') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $administrador->active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $administrador->active ? 'Ativo' : 'Bloqueado' }}
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex flex-wrap justify-content-end gap-1">
                                    <a href="{{ route('admin.users.show', $administrador) }}" class="btn btn-primary btn-sm">Detalhes</a>
                                    @if ($administrador->is(auth()->user()))
                                        <span class="small text-muted align-self-center">Conta atual</span>
                                    @else
                                        @if ($administrador->active)
                                            <form method="POST" action="{{ route('admin.users.block', $administrador) }}"
                                                  onsubmit="return confirm('Deseja bloquear este administrador? Ele perderá imediatamente o acesso ao sistema.')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-warning btn-sm">Bloquear</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.unblock', $administrador) }}"
                                                  onsubmit="return confirm('Deseja desbloquear este administrador?')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-success btn-sm">Desbloquear</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.users.destroy', $administrador) }}"
                                              onsubmit="return confirm('Deseja excluir este administrador? Ele perderá o acesso ao sistema.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm">Excluir</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Nenhum administrador cadastrado.
                            </td>
                        </tr>
                    @endforelse

                    <tr class="d-none" data-no-admin-results>
                        <td colspan="6" class="text-center text-muted py-4">
                            Nenhum administrador encontrado.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<style>
    @media (max-width: 767.98px) {
        /* Mostra TODAS as colunas e rola na horizontal (nada escondido); a
           página rola na vertical. Cabeçalho não-fixo no celular. */
        .system-admins-container {
            max-height: none !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
        }
        .system-admins-table thead th {
            position: static !important;
        }
    }
</style>
@endsection

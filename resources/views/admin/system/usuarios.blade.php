@extends('layouts.main')

@section('title', 'Usuários do sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm mb-3">← Voltar ao painel</a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Usuários cadastrados no sistema</h1>
        <p class="dashboard-subtitle mb-0">Clientes, funcionários e proprietários (administradores não entram nesta lista).</p>
    </div>

    <form method="GET" class="mb-4 d-flex gap-2 flex-wrap" style="max-width: 480px;">
        <input type="text" name="q" value="{{ request('q') }}"
               class="form-control" placeholder="Pesquise por nome ou e-mail">
        <button class="btn btn-primary">Buscar</button>
        @if (request('q'))
            <a href="{{ route('admin.system.users') }}" class="btn btn-outline-secondary">Limpar</a>
        @endif
    </form>

    @php
        $tipoLabel = ['client' => 'Cliente', 'employee' => 'Funcionário', 'owner' => 'Proprietário'];
    @endphp

    <div class="dashboard-box p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Tipo</th>
                        <th>Situação</th>
                        <th class="text-end pe-3">Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td class="ps-3 fw-bold text-break">{{ $usuario->name }}</td>
                            <td class="text-break" style="overflow-wrap: anywhere;">{{ $usuario->email }}</td>
                            <td>{{ $usuario->phone ?: '—' }}</td>
                            <td>{{ $tipoLabel[$usuario->type] ?? ucfirst($usuario->type) }}</td>
                            <td>
                                <span class="badge {{ $usuario->active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $usuario->active ? 'Ativo' : 'Bloqueado' }}
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                {{ optional($usuario->created_at)->format('d/m/Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                @if (request('q'))
                                    Nenhum usuário encontrado.
                                @else
                                    Nenhum usuário cadastrado.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $usuarios->links() }}
    </div>
</div>

@endsection

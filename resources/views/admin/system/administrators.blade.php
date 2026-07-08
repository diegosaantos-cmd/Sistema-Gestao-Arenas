@extends('layouts.main')

@section('title', 'Administradores do Sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm mb-3">← Voltar ao painel</a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Administradores do sistema</h1>
        <p class="dashboard-subtitle mb-0">Consulte e gerencie os administradores gerais.</p>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-md-6">
            <div class="input-group arena-search-box shadow-sm">
                <input type="search" class="form-control"
                       placeholder="Nome, e-mail ou telefone"
                       aria-label="Pesquisar administrador"
                       data-admin-search>
                <span class="input-group-text bg-white border-0">
                    <i class="bi bi-search text-secondary"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="dashboard-box p-0 overflow-hidden system-admins-box">
        <div class="table-responsive system-admins-container" style="max-height: 72vh; overflow-y: auto;">
            <table class="table table-sm table-hover align-middle mb-0 small system-admins-table admin-sticky-table"
                   style="table-layout: fixed; width: 100%;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3" style="width: 17%;">Administrador</th>
                        <th style="width: 21%;">E-mail</th>
                        <th class="admin-secondary-column" style="width: 13%;">Telefone</th>
                        <th class="admin-secondary-column" style="width: 13%;">Cadastro</th>
                        <th style="width: 9%;">Situação</th>
                        <th class="text-end pe-3" style="width: 14%;">Ações</th>
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
                                @if ($administrador->is(auth()->user()))
                                    <span class="small text-muted d-none d-md-inline">Conta atual</span>
                                @else
                                    <div class="d-none d-md-flex justify-content-end gap-1">
                                        @if ($administrador->active)
                                            <form method="POST" action="{{ route('admin.users.block', $administrador) }}"
                                                  onsubmit="return confirm('Deseja bloquear este administrador? Ele perderá imediatamente o acesso ao sistema.')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-outline-warning btn-sm">Bloquear</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.unblock', $administrador) }}"
                                                  onsubmit="return confirm('Deseja desbloquear este administrador?')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-outline-success btn-sm">Desbloquear</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.users.destroy', $administrador) }}"
                                              onsubmit="return confirm('Deseja excluir este administrador? Ele perderá o acesso ao sistema.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm">Excluir</button>
                                        </form>
                                    </div>
                                @endif

                                <button type="button"
                                        class="btn btn-outline-primary btn-sm d-md-none px-2 py-1"
                                        data-admin-mobile-details>
                                    Ver mais
                                </button>
                            </td>
                        </tr>

                        <tr class="admin-mobile-details-row d-none d-md-none" data-admin-details>
                            <td colspan="6" class="p-3 bg-light">
                                <div class="row g-3 small">
                                    <div class="col-6">
                                        <span class="fw-bold">Telefone</span><br>
                                        <span class="text-break">{{ $administrador->phone ?: '—' }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold">Cadastro</span><br>
                                        <span>{{ optional($administrador->created_at)->format('d/m/Y H:i') ?? '—' }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold">Última atualização</span><br>
                                        <span>{{ optional($administrador->updated_at)->format('d/m/Y H:i') ?? '—' }}</span>
                                    </div>

                                    @if (! $administrador->is(auth()->user()))
                                        <div class="col-12 d-grid gap-2">
                                            @if ($administrador->active)
                                                <form method="POST" action="{{ route('admin.users.block', $administrador) }}"
                                                      onsubmit="return confirm('Deseja bloquear este administrador? Ele perderá imediatamente o acesso ao sistema.')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-outline-warning btn-sm w-100">Bloquear</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.unblock', $administrador) }}"
                                                      onsubmit="return confirm('Deseja desbloquear este administrador?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-outline-success btn-sm w-100">Desbloquear</button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('admin.users.destroy', $administrador) }}"
                                                  onsubmit="return confirm('Deseja excluir este administrador? Ele perderá o acesso ao sistema.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm w-100">Excluir</button>
                                            </form>
                                        </div>
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
        .system-admins-container {
            max-height: none !important;
            overflow: visible !important;
        }

        .system-admins-box {
            overflow: visible !important;
        }

        .system-admins-table thead th {
            top: 70px;
        }

        .system-admins-table {
            table-layout: fixed !important;
            font-size: .72rem;
        }

        .system-admins-table .admin-secondary-column {
            display: none;
        }

        .system-admins-table th,
        .system-admins-table td {
            padding: .55rem .25rem !important;
        }

        .system-admins-table th:nth-child(1),
        .system-admins-table td:nth-child(1) {
            width: 27% !important;
        }

        .system-admins-table th:nth-child(2),
        .system-admins-table td:nth-child(2) {
            width: 38% !important;
        }

        .system-admins-table th:nth-child(6),
        .system-admins-table td:nth-child(6) {
            width: 17% !important;
        }

        .system-admins-table th:nth-child(7),
        .system-admins-table td:nth-child(7) {
            width: 18% !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.querySelector('[data-admins-body]');
        const search = document.querySelector('[data-admin-search]');
        const noResults = document.querySelector('[data-no-admin-results]');

        function normalize(value) {
            return (value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, '')
                .toLowerCase();
        }

        function closeDetails(except = null) {
            body.querySelectorAll('[data-admin-details]:not(.d-none)').forEach(function (details) {
                if (details === except) return;
                details.classList.add('d-none');
                const button = details.previousElementSibling?.querySelector('[data-admin-mobile-details]');
                if (button) button.textContent = 'Ver mais';
            });
        }

        body.addEventListener('click', function (event) {
            const button = event.target.closest('[data-admin-mobile-details]');
            if (!button) return;

            const details = button.closest('tr').nextElementSibling;
            const opening = details.classList.contains('d-none');
            closeDetails(opening ? details : null);
            details.classList.toggle('d-none', !opening);
            button.textContent = opening ? 'Fechar' : 'Ver mais';
        });

        search.addEventListener('input', function () {
            const term = normalize(search.value);
            let visible = 0;
            closeDetails();

            body.querySelectorAll('[data-admin-row]').forEach(function (row) {
                const matches = normalize(row.dataset.adminSearch).includes(term);
                row.classList.toggle('d-none', !matches);
                row.nextElementSibling.classList.add('d-none');
                if (matches) visible++;
            });

            noResults.classList.toggle('d-none', visible > 0);
        });
    });
</script>
@endsection

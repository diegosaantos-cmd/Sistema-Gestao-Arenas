@extends('layouts.main')

@section('title', 'Funcionários do Sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm mb-3">← Voltar ao painel</a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Funcionários cadastrados nas arenas</h1>
        <p class="dashboard-subtitle mb-0">Funcionários de todas as empresas e arenas.</p>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-md-6">
            <div class="input-group arena-search-box shadow-sm">
                <input type="search"
                       class="form-control"
                       placeholder="Pesquise por nome, cargo, arena ou empresa"
                       aria-label="Pesquisar funcionário"
                       data-employee-search-input>
                <span class="input-group-text bg-white border-0">
                    <i class="bi bi-search text-secondary"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="dashboard-box p-0 overflow-hidden system-employees-box">
        <div class="table-responsive system-employees-container" style="max-height: 72vh; overflow-y: auto;">
            <table class="table table-sm table-hover align-middle mb-0 small system-employees-table admin-sticky-table"
                   style="table-layout: fixed; width: 100%;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3" style="width: 18%;">Funcionário</th>
                        <th style="width: 14%;">Cargo</th>
                        <th class="employee-secondary-column" style="width: 16%;">Arena</th>
                        <th class="employee-secondary-column" style="width: 16%;">Empresa</th>
                        <th style="width: 12%;">Situação</th>
                        <th class="text-end pe-3" style="width: 24%;">Ações</th>
                    </tr>
                </thead>
                <tbody data-employees-body>
                    @forelse ($funcionarios as $funcionario)
                        <tr class="employee-main-row"
                            data-employee-row
                            data-employee-search="{{ $funcionario->user?->name }} {{ $funcionario->user?->email }} {{ $funcionario->user?->phone }} {{ $funcionario->position }} {{ $funcionario->arena?->name }} {{ $funcionario->arena?->owner?->company_name }} {{ $funcionario->arena?->owner?->user?->name }}">
                            <td class="ps-3 fw-bold text-break">
                                {{ $funcionario->user?->name ?? 'Usuário excluído' }}
                            </td>
                            <td class="text-break">{{ $funcionario->position }}</td>
                            <td class="employee-secondary-column text-break">
                                {{ $funcionario->arena?->name ?? '—' }}
                            </td>
                            <td class="employee-secondary-column text-break">
                                {{ $funcionario->arena?->owner?->company_name ?? '—' }}
                            </td>
                            <td>
                                @php($acessoAtivo = (bool) $funcionario->user?->active)
                                <span class="badge {{ ! $acessoAtivo ? 'bg-danger' : ($funcionario->active ? 'bg-success' : 'bg-secondary') }}">
                                    {{ ! $acessoAtivo ? 'Bloqueado' : ($funcionario->active ? 'Ativo' : 'Inativo') }}
                                </span>
                            </td>
                            <td class="text-end pe-3 employee-main-actions">
                                <div class="d-none d-md-flex gap-1 justify-content-end">
                                    @if ($funcionario->user)
                                        <a href="{{ route('admin.users.show', $funcionario->user) }}" class="btn btn-primary btn-sm">Ver detalhes</a>
                                    @endif

                                    @if ($funcionario->user)
                                        @if ($acessoAtivo)
                                            <form method="POST" action="{{ route('admin.users.block', $funcionario->user) }}"
                                                  onsubmit="return confirm('Deseja bloquear este funcionário? Ele perderá imediatamente o acesso ao sistema.')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-warning btn-sm">Bloquear</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.unblock', $funcionario->user) }}"
                                                  onsubmit="return confirm('Deseja desbloquear este funcionário?')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-success btn-sm">Desbloquear</button>
                                            </form>
                                        @endif
                                    @endif

                                    @if ($funcionario->arena)
                                        <form method="POST"
                                              action="{{ route('admin.arenas.employees.destroy', [$funcionario->arena, $funcionario]) }}"
                                              data-employee-delete-form>
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm">Excluir</button>
                                        </form>
                                    @endif
                                </div>

                                @if ($funcionario->user)
                                    <a href="{{ route('admin.users.show', $funcionario->user) }}"
                                       class="btn btn-primary btn-sm d-md-none px-2 py-1 text-nowrap">Ver detalhes</a>
                                @endif
                            </td>
                        </tr>

                        <tr class="employee-mobile-details-row d-none d-md-none" data-employee-details-row>
                            <td colspan="6" class="p-3 bg-light">
                                <div class="row g-3 small" data-employee-details-content>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Nome</span><br>
                                        <span>{{ $funcionario->user?->name ?? 'Usuário excluído' }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Cargo</span><br>
                                        <span>{{ $funcionario->position }}</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="fw-bold text-dark">E-mail</span><br>
                                        <span class="text-break">{{ $funcionario->user?->email ?? '—' }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Telefone</span><br>
                                        <span class="text-break">{{ $funcionario->user?->phone ?: '—' }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Nível de acesso</span><br>
                                        <span>{{ $funcionario->access_level === 'managerial' ? 'Gerencial' : 'Básico' }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Situação</span><br>
                                        <span class="badge {{ ! $acessoAtivo ? 'bg-danger' : ($funcionario->active ? 'bg-success' : 'bg-secondary') }}">
                                            {{ ! $acessoAtivo ? 'Bloqueado' : ($funcionario->active ? 'Ativo' : 'Inativo') }}
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Arena</span><br>
                                        <span>{{ $funcionario->arena?->name ?? '—' }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Empresa</span><br>
                                        <span>{{ $funcionario->arena?->owner?->company_name ?? '—' }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Proprietário</span><br>
                                        <span>{{ $funcionario->arena?->owner?->user?->name ?? '—' }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Cadastrado por</span><br>
                                        <span>{{ $funcionario->createdBy?->name ?? '—' }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Cadastro</span><br>
                                        @if ($funcionario->created_at)
                                            <span>{{ $funcionario->created_at->format('d/m/Y') }}</span><br>
                                            <span class="text-muted">{{ $funcionario->created_at->format('H:i') }}</span>
                                        @else
                                            —
                                        @endif
                                    </div>
                                    <div class="col-6">
                                        <span class="fw-bold text-dark">Última atualização</span><br>
                                        @if ($funcionario->updated_at)
                                            <span>{{ $funcionario->updated_at->format('d/m/Y') }}</span><br>
                                            <span class="text-muted">{{ $funcionario->updated_at->format('H:i') }}</span>
                                        @else
                                            —
                                        @endif
                                    </div>

                                    @if ($funcionario->arena)
                                        <div class="col-12" data-mobile-delete-action>
                                            @if ($funcionario->user)
                                                @if ($acessoAtivo)
                                                    <form method="POST" action="{{ route('admin.users.block', $funcionario->user) }}"
                                                          class="mb-2"
                                                          onsubmit="return confirm('Deseja bloquear este funcionário? Ele perderá imediatamente o acesso ao sistema.')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="btn btn-warning btn-sm w-100">Bloquear funcionário</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.users.unblock', $funcionario->user) }}"
                                                          class="mb-2"
                                                          onsubmit="return confirm('Deseja desbloquear este funcionário?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="btn btn-success btn-sm w-100">Desbloquear funcionário</button>
                                                    </form>
                                                @endif
                                            @endif

                                            <form method="POST"
                                                  action="{{ route('admin.arenas.employees.destroy', [$funcionario->arena, $funcionario]) }}"
                                                  onsubmit="return confirm('Deseja excluir este funcionário? Ele perderá o acesso ao sistema.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger btn-sm w-100">Excluir funcionário</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-employees>
                            <td colspan="6" class="text-center text-muted py-4">
                                Nenhum funcionário cadastrado.
                            </td>
                        </tr>
                    @endforelse

                    <tr class="d-none" data-no-employee-results>
                        <td colspan="6" class="text-center text-muted py-4">
                            Nenhum funcionário encontrado.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="employeeDeleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Excluir funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este funcionário?</p>
                <div class="alert alert-danger mb-0">
                    O acesso do funcionário será removido e ele deixará de fazer parte da arena.
                    As reservas e os demais registros da arena não serão excluídos.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" data-confirm-employee-delete>Sim, excluir</button>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed start-50 translate-middle-x px-3 w-100"
     style="top: 82px; z-index: 1060; max-width: 680px;"
     data-employee-feedback></div>

<style>
    @media (max-width: 767.98px) {
        .system-employees-container {
            max-height: none !important;
            overflow: visible !important;
        }

        .system-employees-box {
            overflow: visible !important;
        }

        .system-employees-table thead th {
            top: 70px;
        }

        .system-employees-table {
            table-layout: fixed !important;
            font-size: .72rem;
        }

        .system-employees-table .employee-secondary-column {
            display: none;
        }

        .system-employees-table th,
        .system-employees-table td {
            padding: .55rem .25rem !important;
            vertical-align: middle;
        }

        .system-employees-table th:nth-child(1),
        .system-employees-table td:nth-child(1) {
            width: 34% !important;
        }

        .system-employees-table th:nth-child(2),
        .system-employees-table td:nth-child(2) {
            width: 23% !important;
        }

        .system-employees-table th:nth-child(5),
        .system-employees-table td:nth-child(5) {
            width: 18% !important;
        }

        .system-employees-table th:nth-child(6),
        .system-employees-table td:nth-child(6) {
            width: 25% !important;
        }

        .system-employees-table .employee-main-actions .btn {
            font-size: .67rem;
            padding: .3rem .15rem;
        }

        .system-employees-table .employee-mobile-details-row td {
            padding: .85rem !important;
        }
    }

    @media (min-width: 768px) {
        .employee-details-dialog {
            max-width: 620px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.querySelector('[data-employees-body]');
        const searchInput = document.querySelector('[data-employee-search-input]');
        const noResults = document.querySelector('[data-no-employee-results]');
        const feedback = document.querySelector('[data-employee-feedback]');
        const modalElement = document.getElementById('employeeDeleteConfirmModal');
        const confirmModal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const confirmButton = modalElement.querySelector('[data-confirm-employee-delete]');
        let pendingDeleteForm = null;

        function normalizeSearch(value) {
            return (value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, '')
                .toLowerCase();
        }

        function showFeedback(message, type = 'success') {
            feedback.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show shadow" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            `;

            window.setTimeout(function () {
                feedback.innerHTML = '';
            }, 4500);
        }

        body.addEventListener('submit', function (event) {
            const form = event.target.closest('[data-employee-delete-form]');

            if (!form) {
                return;
            }

            event.preventDefault();
            pendingDeleteForm = form;
            confirmModal.show();
        });

        confirmButton.addEventListener('click', async function () {
            if (!pendingDeleteForm) {
                return;
            }

            const form = pendingDeleteForm;
            const mainRow = form.closest('tr').classList.contains('employee-main-row')
                ? form.closest('tr')
                : form.closest('tr').previousElementSibling;
            const detailsRow = mainRow.nextElementSibling;

            confirmButton.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                });

                if (!response.ok) {
                    throw new Error('Não foi possível excluir o funcionário.');
                }

                confirmModal.hide();
                mainRow.remove();
                detailsRow?.remove();
                showFeedback('Funcionário excluído com sucesso.');
            } catch (error) {
                confirmModal.hide();
                showFeedback(error.message, 'danger');
            } finally {
                confirmButton.disabled = false;
                pendingDeleteForm = null;
            }
        });

        searchInput.addEventListener('input', function () {
            const term = normalizeSearch(searchInput.value);
            let visible = 0;

            body.querySelectorAll('[data-employee-row]').forEach(function (row) {
                const matches = normalizeSearch(row.dataset.employeeSearch).includes(term);
                const detailsRow = row.nextElementSibling;

                row.classList.toggle('d-none', !matches);
                detailsRow.classList.add('d-none');

                if (matches) {
                    visible++;
                }
            });

            noResults.classList.toggle('d-none', visible > 0);
        });
    });
</script>
@endsection

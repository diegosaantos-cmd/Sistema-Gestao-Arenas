@extends('layouts.main')

@section('title', 'Administração Geral')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <div class="mb-5">
        <span class="badge bg-danger mb-2">ADMINISTRADOR GERAL</span>
        <h1 class="dashboard-title mb-1">Painel administrativo</h1>
        <p class="dashboard-subtitle mb-0">Escolha uma área do sistema para gerenciar.</p>
    </div>

    <div class="row g-4">
        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.owners.index') }}" class="text-decoration-none text-reset">
                <div class="card shadow-sm border-0 h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h4 class="text-secondary">Empresas</h4>
                            <span class="text-muted small">Ver</span>
                        </div>
                        <h1 class="fw-bold mb-1">{{ $resumo['proprietarios'] }}</h1>
                        <div class="small text-muted">Empresas e proprietários cadastrados</div>
                        <div class="small mt-1">
                            <span class="text-success">{{ $resumo['proprietarios_ativos'] }} ativas</span>
                            · <span class="text-muted">{{ $resumo['proprietarios_inativos'] }} inativas</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.system.arenas') }}" class="text-decoration-none text-reset">
                <div class="card shadow-sm border-0 h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h4 class="text-secondary">Arenas</h4>
                            <span class="text-muted small">Ver</span>
                        </div>
                        <h1 class="fw-bold mb-1">{{ $resumo['arenas'] }}</h1>
                        <div class="small text-muted">Arenas cadastradas</div>
                        <div class="small mt-1">
                            <span class="text-success">{{ $resumo['arenas_ativas'] }} ativas</span>
                            · <span class="text-muted">{{ $resumo['arenas_inativas'] }} inativas</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.system.courts') }}" class="text-decoration-none text-reset">
                <div class="card shadow-sm border-0 h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h4 class="text-secondary">Quadras</h4>
                            <span class="text-muted small">Ver</span>
                        </div>
                        <h1 class="fw-bold mb-1">{{ $resumo['quadras'] }}</h1>
                        <div class="small text-muted">Em todas as arenas</div>
                        <div class="small mt-1">
                            <span class="text-success">{{ $resumo['quadras_ativas'] }} ativas</span>
                            · <span class="text-muted">{{ $resumo['quadras_inativas'] }} inativas</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.system.clients') }}" class="text-decoration-none text-reset">
                <div class="card shadow-sm border-0 h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h4 class="text-secondary">Clientes</h4>
                            <span class="text-muted small">Ver</span>
                        </div>
                        <h1 class="fw-bold mb-1">{{ $resumo['clientes'] }}</h1>
                        <div class="small text-muted">Clientes cadastrados</div>
                        <div class="small mt-1">
                            <span class="text-success">{{ $resumo['clientes_ativos'] }} ativos</span>
                            · <span class="text-muted">{{ $resumo['clientes_inativos'] }} inativos</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mt-1 align-items-start">
        <div class="col-lg-8">
            <div class="row g-4">
                <div class="col-md-4">
                    <a href="{{ route('admin.system.users') }}" class="text-decoration-none text-reset">
                        <div class="card shadow-sm border-0 h-100 card-hover">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h4 class="text-secondary">Total de usuários</h4>
                                    <span class="text-muted small">Ver</span>
                                </div>
                                <h1 class="fw-bold mb-1">{{ $resumo['usuarios'] }}</h1>
                                <div class="small text-muted">Clientes, funcionários e proprietários</div>
                                <div class="small mt-1">
                                    <span class="text-success">{{ $resumo['usuarios_ativos'] }} ativos</span>
                                    · <span class="text-muted">{{ $resumo['usuarios_inativos'] }} inativos</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="{{ route('admin.system.employees') }}" class="text-decoration-none text-reset">
                        <div class="card shadow-sm border-0 h-100 card-hover">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h4 class="text-secondary">Funcionários</h4>
                                    <span class="text-muted small">Ver</span>
                                </div>
                                <h1 class="fw-bold mb-1">{{ $resumo['funcionarios'] }}</h1>
                                <div class="small text-muted">Funcionários cadastrados nas arenas</div>
                                <div class="small mt-1">
                                    <span class="text-success">{{ $resumo['funcionarios_ativos'] }} ativos</span>
                                    · <span class="text-muted">{{ $resumo['funcionarios_inativos'] }} inativos</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="{{ route('admin.system.administrators') }}" class="text-decoration-none text-reset">
                        <div class="card shadow-sm border-0 h-100 card-hover">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h4 class="text-secondary">Administradores do sistema</h4>
                                    <span class="text-muted small">Ver</span>
                                </div>
                                <h1 class="fw-bold mb-1">{{ $resumo['administradores'] }}</h1>
                                <div class="small text-muted">Controle administrativo</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dashboard-box quick-actions-box">
                <h2 class="section-title">Ações rápidas</h2>
                <div class="row g-3 mt-2">
            <div class="col-6 quick-action-cell">
                <button type="button" class="btn btn-outline-dark btn-lg w-100"
                        data-bs-toggle="modal" data-bs-target="#adminProfileModal">
                    <i class="bi bi-person-circle me-1"></i>
                    Perfil
                </button>
            </div>
            <div class="col-6 quick-action-cell">
                <button type="button" class="btn btn-outline-dark btn-lg w-100"
                        data-bs-toggle="modal" data-bs-target="#newAdminModal">
                    <i class="bi bi-person-plus me-1"></i>
                    Novo admin
                </button>
            </div>
            <div class="col-12 quick-action-cell">
                <a href="{{ route('admin.aparencia') }}" class="btn btn-outline-dark btn-lg w-100">
                    <i class="bi bi-image me-1"></i>
                    Aparência da tela inicial
                </a>
            </div>
            <div class="col-12 quick-action-cell">
                <a href="{{ route('admin.feedbacks') }}" class="btn btn-outline-dark btn-lg w-100 position-relative">
                    <i class="bi bi-chat-left-dots me-1"></i>
                    Visualizar sugestões e bugs
                    @if (($feedbacksNaoLidos ?? 0) > 0)
                        <span class="badge rounded-pill bg-danger ms-2">
                            {{ $feedbacksNaoLidos > 99 ? '99+' : $feedbacksNaoLidos }}
                            <span class="visually-hidden">não lidos</span>
                        </span>
                    @endif
                </a>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="adminProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Perfil do administrador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <span class="small fw-bold text-dark">Nome</span><br>
                        <span>{{ auth()->user()->name }}</span>
                    </div>
                    <div class="col-12">
                        <span class="small fw-bold text-dark">E-mail</span><br>
                        <span class="text-break">{{ auth()->user()->email }}</span>
                    </div>
                    <div class="col-6">
                        <span class="small fw-bold text-dark">Telefone</span><br>
                        <span>{{ auth()->user()->phone ?: '—' }}</span>
                    </div>
                    <div class="col-6">
                        <span class="small fw-bold text-dark">Tipo da conta</span><br>
                        <span>Administrador</span>
                    </div>
                    <div class="col-6">
                        <span class="small fw-bold text-dark">Situação</span><br>
                        <span class="badge {{ auth()->user()->active ? 'bg-success' : 'bg-danger' }}">
                            {{ auth()->user()->active ? 'Ativo' : 'Bloqueado' }}
                        </span>
                    </div>
                    <div class="col-6">
                        <span class="small fw-bold text-dark">Cadastro</span><br>
                        @if (auth()->user()->created_at)
                            <span>{{ auth()->user()->created_at->format('d/m/Y') }}</span><br>
                            <span class="small text-muted">{{ auth()->user()->created_at->format('H:i') }}</span>
                        @else
                            <span>—</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between flex-wrap gap-2">
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-warning btn-sm"
                            data-bs-toggle="modal" data-bs-target="#adminProfileEditModal">
                        <i class="bi bi-pencil me-1"></i> Editar perfil
                    </button>
                    <button type="button" class="btn btn-warning btn-sm"
                            data-bs-toggle="modal" data-bs-target="#adminPasswordModal">
                        Trocar senha
                    </button>
                </div>
                <button type="button" class="btn btn-danger btn-sm"
                        data-bs-toggle="modal" data-bs-target="#deleteAdminModal">
                    Excluir conta
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="adminProfileEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.profile.update') }}">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Editar perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="adminProfileName">Nome</label>
                        <input type="text" class="form-control" id="adminProfileName"
                               name="name" maxlength="255" required
                               value="{{ old('name', auth()->user()->name) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="adminProfileEmail">E-mail</label>
                        <input type="email" class="form-control" id="adminProfileEmail"
                               name="email" maxlength="255" required
                               value="{{ old('email', auth()->user()->email) }}">
                    </div>
                    <div>
                        <label class="form-label" for="adminProfilePhone">
                            Telefone <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <input type="text" class="form-control" id="adminProfilePhone"
                               name="phone" maxlength="30"
                               value="{{ old('phone', auth()->user()->phone) }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="adminPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.profile.password') }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Trocar senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="adminCurrentPassword">Senha atual</label>
                        <input type="password" class="form-control" id="adminCurrentPassword"
                               name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="adminNewPassword">Nova senha</label>
                        <input type="password" class="form-control" id="adminNewPassword"
                               name="password" minlength="8" required autocomplete="new-password">
                    </div>
                    <div>
                        <label class="form-label" for="adminNewPasswordConfirmation">Confirmar nova senha</label>
                        <input type="password" class="form-control" id="adminNewPasswordConfirmation"
                               name="password_confirmation" minlength="8" required autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success">Alterar senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.profile.destroy') }}">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Excluir conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        Sua conta de administrador será excluída, todas as sessões serão encerradas
                        e você perderá imediatamente o acesso ao painel.
                    </div>
                    <label class="form-label" for="deleteAdminPassword">Digite sua senha para confirmar</label>
                    <input type="password" class="form-control" id="deleteAdminPassword"
                           name="delete_password" required autocomplete="current-password">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger">Excluir definitivamente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="newAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.administrators.store') }}" method="POST" data-new-admin-form>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Novo administrador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" data-admin-errors></div>
                    <div class="mb-3">
                        <label class="form-label" for="adminName">Nome</label>
                        <input class="form-control" id="adminName" name="name" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="adminEmail">E-mail</label>
                        <input type="email" class="form-control" id="adminEmail" name="email"
                               placeholder="admin@email.com" autocomplete="email" data-mask-email
                               required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="adminPhone">Telefone</label>
                        <input class="form-control" id="adminPhone" name="phone"
                               inputmode="numeric" autocomplete="tel" maxlength="15"
                               placeholder="(00) 00000-0000" data-mask-phone required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="adminPassword">Senha</label>
                            <input type="password" class="form-control" id="adminPassword"
                                   name="password" minlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="adminPasswordConfirmation">Confirmar senha</label>
                            <input type="password" class="form-control" id="adminPasswordConfirmation"
                                   name="password_confirmation" minlength="8" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success" data-save-admin>Cadastrar administrador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="quickSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" data-quick-search-title>Pesquisar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="input-group arena-search-box shadow-sm mb-3">
                    <input type="search" class="form-control"
                           placeholder="Digite para pesquisar..."
                           autocomplete="off"
                           data-quick-search-input>
                    <span class="input-group-text bg-white border-0">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                </div>
                <div class="text-center text-muted py-4" data-quick-search-results>
                    Digite um nome para começar.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed start-50 translate-middle-x px-3 w-100"
     style="top: 82px; z-index: 1090; max-width: 680px;"
     data-dashboard-feedback></div>

<style>
    .quick-actions-box {
        width: 100%;
        padding: 30px;
    }

    .quick-action-cell {
        display: flex;
    }

    .quick-search-results {
        max-height: 55vh;
        overflow-y: auto;
        padding-right: .25rem;
    }

</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrf = @json(csrf_token());
        const searchEndpoint = @json(route('admin.quick-search'));
        const searchModalElement = document.getElementById('quickSearchModal');
        const searchModal = bootstrap.Modal.getOrCreateInstance(searchModalElement);
        const searchTitle = searchModalElement.querySelector('[data-quick-search-title]');
        const searchInput = searchModalElement.querySelector('[data-quick-search-input]');
        const searchResults = searchModalElement.querySelector('[data-quick-search-results]');
        const feedback = document.querySelector('[data-dashboard-feedback]');
        const adminForm = document.querySelector('[data-new-admin-form]');
        const adminErrors = adminForm.querySelector('[data-admin-errors]');
        const adminButton = adminForm.querySelector('[data-save-admin]');
        let searchType = null;
        let searchTimer = null;
        let searchRequest = null;

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = value ?? '';
            return element.innerHTML;
        }

        function showFeedback(message, type = 'success') {
            feedback.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show shadow" role="alert">
                    ${escapeHtml(message)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            `;
            window.setTimeout(() => feedback.replaceChildren(), 4500);
        }

        function onlyNumbers(value) {
            return String(value || '').replace(/\D/g, '');
        }

        function maskCpf(value) {
            const digits = onlyNumbers(value).slice(0, 11);
            return digits
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }

        function maskPhone(value) {
            const digits = onlyNumbers(value).slice(0, 11);

            if (digits.length <= 10) {
                return digits
                    .replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            }

            return digits
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d)/, '$1-$2');
        }

        function normalizeEmail(value) {
            return String(value || '').trim().toLowerCase().replace(/\s+/g, '');
        }

        document.querySelectorAll('[data-mask-cpf]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = maskCpf(input.value);
            });
        });

        document.querySelectorAll('[data-mask-phone]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = maskPhone(input.value);
            });
        });

        document.querySelectorAll('[data-mask-email]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = normalizeEmail(input.value);
            });
            input.addEventListener('blur', function () {
                input.value = normalizeEmail(input.value);
            });
        });

        function actionForm(url, method, label, style, message) {
            if (!url) {
                return '';
            }

            return `
                <form action="${escapeHtml(url)}" method="POST"
                      onsubmit="return confirm('${escapeHtml(message)}')">
                    <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                    <input type="hidden" name="_method" value="${method}">
                    <button class="btn btn-${style} btn-sm">${escapeHtml(label)}</button>
                </form>
            `;
        }

        function renderArena(item) {
            const toggle = item.ativo
                ? actionForm(item.desativar_url, 'PATCH', 'Desativar', 'outline-warning',
                    'Deseja desativar esta arena? As reservas futuras serão canceladas e ela deixará de aparecer para os clientes.')
                : actionForm(item.ativar_url, 'PATCH', 'Ativar', 'outline-success',
                    'Deseja ativar esta arena?');

            return `
                <div class="border rounded p-3 text-start">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <strong>${escapeHtml(item.nome)}</strong>
                            <div class="small text-muted">Empresa: ${escapeHtml(item.empresa)}</div>
                            <span class="badge ${item.ativo ? 'bg-success' : 'bg-secondary'} mt-1">
                                ${item.ativo ? 'Ativa' : 'Desativada'}
                            </span>
                        </div>
                        <div class="d-flex flex-wrap align-items-start gap-1">
                            <a class="btn btn-outline-primary btn-sm" href="${escapeHtml(item.ver_url)}">Ver</a>
                            ${toggle}
                            ${actionForm(item.excluir_url, 'DELETE', 'Excluir', 'outline-danger',
                                'Deseja excluir esta arena? As reservas futuras serão canceladas e o histórico será preservado.')}
                        </div>
                    </div>
                </div>
            `;
        }

        function renderCompany(item) {
            const toggle = item.ativo
                ? actionForm(item.desativar_url, 'PATCH', 'Desativar', 'outline-warning',
                    'Deseja desativar esta empresa? O proprietário perderá o acesso e suas arenas serão desativadas.')
                : actionForm(item.ativar_url, 'PATCH', 'Ativar', 'outline-success',
                    'Deseja ativar esta empresa e liberar novamente o acesso do proprietário?');

            return `
                <div class="border rounded p-3 text-start">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <strong>${escapeHtml(item.nome)}</strong>
                            <div class="small text-muted">Proprietário: ${escapeHtml(item.proprietario)}</div>
                            <div class="small text-muted">CPF/CNPJ: ${escapeHtml(item.documento)}</div>
                            <span class="badge ${item.ativo ? 'bg-success' : 'bg-secondary'} mt-1">
                                ${item.ativo ? 'Ativa' : 'Desativada'}
                            </span>
                        </div>
                        <div class="d-flex flex-wrap align-items-start gap-1">
                            <a class="btn btn-outline-primary btn-sm" href="${escapeHtml(item.ver_url)}">Ver</a>
                            ${toggle}
                            ${actionForm(item.excluir_url, 'DELETE', 'Excluir', 'outline-danger',
                                'Deseja excluir esta empresa? O acesso será removido e suas arenas serão desativadas.')}
                        </div>
                    </div>
                </div>
            `;
        }

        function renderCourt(item) {
            const toggle = item.ativo
                ? actionForm(item.desativar_url, 'PATCH', 'Desativar', 'outline-warning',
                    'Deseja desativar esta quadra? As reservas futuras serão canceladas.')
                : actionForm(item.ativar_url, 'PATCH', 'Ativar', 'outline-success',
                    'Deseja ativar esta quadra?');

            return `
                <div class="border rounded p-3 text-start">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <strong>${escapeHtml(item.nome)}</strong>
                            <div class="small text-muted">Arena: ${escapeHtml(item.arena)}</div>
                            <div class="small text-muted">Empresa: ${escapeHtml(item.empresa)}</div>
                            <span class="badge ${item.ativo ? 'bg-success' : 'bg-secondary'} mt-1">
                                ${item.ativo ? 'Ativa' : 'Desativada'}
                            </span>
                        </div>
                        <div class="d-flex flex-wrap align-items-start gap-1">
                            <a class="btn btn-outline-primary btn-sm" href="${escapeHtml(item.ver_url)}">Ver</a>
                            ${toggle}
                            ${actionForm(item.excluir_url, 'DELETE', 'Excluir', 'outline-danger',
                                'Deseja excluir esta quadra? As reservas futuras serão canceladas e o histórico será preservado.')}
                        </div>
                    </div>
                </div>
            `;
        }

        function renderUser(item) {
            const context = item.tipo === 'Funcionário'
                ? `<div class="small text-muted">Arena: ${escapeHtml(item.arena || '—')} · Empresa: ${escapeHtml(item.empresa || '—')}</div>`
                : '';
            const toggle = !item.pode_alterar ? '' : item.ativo
                ? actionForm(item.bloquear_url, 'PATCH', 'Bloquear', 'outline-warning',
                    'Deseja bloquear este usuário? Ele perderá imediatamente o acesso ao sistema.')
                : actionForm(item.desbloquear_url, 'PATCH', 'Desbloquear', 'outline-success',
                    'Deseja desbloquear este usuário?');
            const destroy = item.pode_alterar
                ? actionForm(item.excluir_url, 'DELETE', 'Excluir', 'outline-danger',
                    'Deseja excluir este usuário? O acesso será removido e o histórico será preservado.')
                : '';

            return `
                <div class="border rounded p-3 text-start">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <strong>${escapeHtml(item.nome)}</strong>
                            <div class="small text-muted">${escapeHtml(item.tipo)} · ${escapeHtml(item.email)}</div>
                            ${context}
                            <span class="badge ${item.ativo ? 'bg-success' : 'bg-danger'} mt-1">
                                ${item.ativo ? 'Ativo' : 'Bloqueado'}
                            </span>
                        </div>
                        <div class="d-flex flex-wrap align-items-start gap-1">
                            <a class="btn btn-outline-primary btn-sm" href="${escapeHtml(item.ver_url)}">Ver</a>
                            ${toggle}
                            ${destroy}
                        </div>
                    </div>
                </div>
            `;
        }

        async function performSearch() {
            const term = searchInput.value.trim();

            if (!term) {
                searchRequest?.abort();
                searchResults.className = 'text-center text-muted py-4';
                searchResults.textContent = 'Digite um nome para começar.';
                return;
            }

            searchRequest?.abort();
            searchRequest = new AbortController();
            searchResults.className = 'text-center py-4';
            searchResults.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>';

            try {
                const url = new URL(searchEndpoint, window.location.origin);
                url.searchParams.set('tipo', searchType);
                url.searchParams.set('busca', term);
                const response = await fetch(url, {
                    headers: {'Accept': 'application/json'},
                    signal: searchRequest.signal,
                });

                if (!response.ok) {
                    throw new Error('Não foi possível realizar a pesquisa.');
                }

                const data = await response.json();
                if (!data.resultados.length) {
                    searchResults.className = 'text-center text-muted py-4';
                    searchResults.textContent = 'Nenhum resultado encontrado.';
                    return;
                }

                searchResults.className = 'd-grid gap-2 quick-search-results';
                searchResults.innerHTML = data.resultados.map(function (item) {
                    if (searchType === 'empresa') return renderCompany(item);
                    if (searchType === 'arena') return renderArena(item);
                    if (searchType === 'quadra') return renderCourt(item);
                    return renderUser(item);
                }).join('');
            } catch (error) {
                if (error.name !== 'AbortError') {
                    searchResults.className = 'text-center text-danger py-4';
                    searchResults.textContent = error.message;
                }
            }
        }

        document.querySelectorAll('[data-quick-search]').forEach(function (button) {
            button.addEventListener('click', function () {
                searchType = button.dataset.quickSearch;
                searchTitle.textContent = button.dataset.searchTitle;
                searchInput.value = '';
                searchResults.className = 'text-center text-muted py-4';
                searchResults.textContent = 'Digite um nome para começar.';
                searchModal.show();
                searchModalElement.addEventListener('shown.bs.modal', () => searchInput.focus(), {once: true});
            });
        });

        searchInput.addEventListener('input', function () {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(performSearch, 180);
        });

        adminForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            adminErrors.classList.add('d-none');
            adminButton.disabled = true;

            try {
                const formData = new FormData(adminForm);
                formData.set('email', normalizeEmail(formData.get('email')));

                const response = await fetch(adminForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                });
                const data = await response.json();

                if (!response.ok) {
                    const errors = Object.values(data.errors || {}).flat();
                    throw new Error(errors.join('<br>') || 'Não foi possível cadastrar o administrador.');
                }

                bootstrap.Modal.getInstance(document.getElementById('newAdminModal')).hide();
                adminForm.reset();
                showFeedback(data.message);
            } catch (error) {
                adminErrors.innerHTML = error.message;
                adminErrors.classList.remove('d-none');
            } finally {
                adminButton.disabled = false;
            }
        });
    });
</script>
@endsection

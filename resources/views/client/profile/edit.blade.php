@extends('layouts.main')

@section('title', 'Meu perfil')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <div class="mb-3">
        <a href="{{ route('dashboard') }}" class="btn btn-dark btn-sm">
            ← Voltar ao painel
        </a>
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Meu perfil</h1>
        <p class="dashboard-subtitle mb-0">Gerencie suas informações pessoais</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="dashboard-box mx-auto" style="max-width: 900px;">
        <form method="POST" action="{{ route('client.profile.update') }}" id="perfilForm">
            @csrf
            @method('PATCH')

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h2 class="section-title mb-1" style="font-size: 1.5rem;">Dados pessoais</h2>
                    <p class="text-muted mb-0">Clique na caneta para editar um campo.</p>
                </div>

                <button type="submit" class="btn btn-secondary px-4" id="btnSalvarDados" disabled>
                    <i class="bi bi-check-circle me-2"></i> Salvar dados
                </button>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="name" class="form-label text-muted">Nome</label>
                    <div class="input-group">
                        <input type="text" id="name" name="name" class="form-control border-end-0 campo-editavel"
                               value="{{ old('name', $user->name) }}" maxlength="100" readonly required>
                        <button class="btn bg-white text-secondary border border-start-0 editar-campo"
                                style="border-color: var(--bs-border-color) !important;" type="button"
                                data-campo="name" aria-label="Editar nome" title="Editar nome">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label text-muted">E-mail</label>
                    <div class="input-group">
                        <input type="email" id="email" name="email" class="form-control border-end-0 campo-editavel"
                               value="{{ old('email', $user->email) }}" maxlength="150" readonly required>
                        <button class="btn bg-white text-secondary border border-start-0 editar-campo"
                                style="border-color: var(--bs-border-color) !important;" type="button"
                                data-campo="email" aria-label="Editar e-mail" title="Editar e-mail">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label text-muted">Telefone / WhatsApp</label>
                    <div class="input-group">
                        <input type="text" id="phone" name="phone" class="form-control border-end-0 campo-editavel"
                               value="{{ old('phone', $user->phone) }}" maxlength="20"
                               placeholder="Não informado" readonly>
                        <button class="btn bg-white text-secondary border border-start-0 editar-campo"
                                style="border-color: var(--bs-border-color) !important;" type="button"
                                data-campo="phone" aria-label="Editar telefone" title="Editar telefone">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="date_of_birth" class="form-label text-muted">Data de nascimento</label>
                    <div class="input-group">
                        <input type="date" id="date_of_birth" name="date_of_birth"
                               class="form-control border-end-0 campo-editavel"
                               value="{{ old('date_of_birth', $client?->date_of_birth) }}"
                               max="{{ now()->toDateString() }}" readonly>
                        <button class="btn bg-white text-secondary border border-start-0 editar-campo"
                                style="border-color: var(--bs-border-color) !important;" type="button"
                                data-campo="date_of_birth" aria-label="Editar data de nascimento"
                                title="Editar data de nascimento">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-muted">Tipo da conta</label>
                    <input type="text" class="form-control bg-light"
                           value="{{ ['client' => 'Cliente', 'owner' => 'Proprietário', 'employee' => 'Funcionário'][$user->type] ?? ucfirst($user->type) }}"
                           readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-muted">Cadastrado desde</label>
                    <input type="text" class="form-control bg-light"
                           value="{{ optional($user->created_at)->format('d/m/Y') ?? '—' }}" readonly>
                </div>
            </div>
        </form>

        <hr class="my-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <button type="button" class="btn btn-outline-danger"
                    data-bs-toggle="modal" data-bs-target="#modalExcluirConta">
                <i class="bi bi-trash me-2"></i> Excluir conta
            </button>

            <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                    data-bs-target="#modalTrocarSenha">
                <i class="bi bi-shield-lock me-2"></i> Trocar senha
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExcluirConta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('client.profile.destroy') }}">
                @csrf
                @method('DELETE')

                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i> Excluir conta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>Esta ação é permanente.</strong>
                        Sua conta e seus dados serão excluídos definitivamente e não poderão ser recuperados.
                    </div>

                    <p class="text-muted">
                        A exclusão não será permitida caso existam horários agendados ou pagamentos pendentes.
                    </p>

                    @if ($errors->deleteAccount->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->deleteAccount->all() as $erro)
                                    <li>{{ $erro }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <label for="delete_password" class="form-label">Digite sua senha para confirmar</label>
                    <input type="password" id="delete_password" name="delete_password"
                           class="form-control" autocomplete="current-password" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i> Excluir permanentemente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTrocarSenha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('client.profile.password') }}">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title">Trocar senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Senha atual</label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-control" autocomplete="current-password" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Nova senha</label>
                        <input type="password" id="password" name="password"
                               class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Confirmar nova senha</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i> Salvar nova senha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('perfilForm');
        const campos = Array.from(form.querySelectorAll('.campo-editavel'));
        const salvar = document.getElementById('btnSalvarDados');
        const valoresIniciais = new Map(campos.map(campo => [campo.name, campo.value]));

        function atualizarBotaoSalvar() {
            const alterado = campos.some(campo => campo.value !== valoresIniciais.get(campo.name));
            salvar.disabled = !alterado;
            salvar.classList.toggle('btn-secondary', !alterado);
            salvar.classList.toggle('btn-success', alterado);
        }

        document.querySelectorAll('.editar-campo').forEach(botao => {
            botao.addEventListener('click', function () {
                const campo = document.getElementById(this.dataset.campo);
                campo.readOnly = false;
                campo.classList.add('border-warning');
                this.style.setProperty('border-color', 'var(--bs-warning)', 'important');
                campo.focus();

                if (campo.type !== 'date') {
                    campo.select();
                }
            });
        });

        campos.forEach(campo => campo.addEventListener('input', atualizarBotaoSalvar));

        @if ($errors->has('current_password') || $errors->has('password'))
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTrocarSenha')).show();
        @endif

        @if ($errors->deleteAccount->any())
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExcluirConta')).show();
        @endif
    });
</script>

@endsection

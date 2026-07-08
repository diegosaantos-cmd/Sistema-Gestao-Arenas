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

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="section-title mb-0" style="font-size: 1.5rem;">Dados pessoais</h2>
            <button type="button" class="btn btn-warning" id="btnEditarPessoais">
                ✏️ Editar
            </button>
        </div>

        {{-- Visualização --}}
        <div id="pessoaisView" class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">Nome</div>
                <div class="fw-semibold">{{ $user->name }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">E-mail</div>
                <div class="fw-semibold">{{ $user->email }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Telefone / WhatsApp</div>
                <div class="fw-semibold">{{ $user->phone ?: '—' }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Data de nascimento</div>
                <div class="fw-semibold">
                    {{ $client?->date_of_birth ? \Carbon\Carbon::parse($client->date_of_birth)->format('d/m/Y') : '—' }}
                </div>
            </div>
        </div>

        {{-- Edição --}}
        <form method="POST" action="{{ route('client.profile.update') }}" id="pessoaisForm" class="d-none">
            @csrf
            @method('PATCH')

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="name" class="form-label text-muted">Nome</label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name', $user->name) }}" maxlength="100" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label text-muted">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="{{ old('email', $user->email) }}" maxlength="150" required>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label text-muted">Telefone / WhatsApp</label>
                    <input type="text" id="phone" name="phone" class="form-control"
                           value="{{ old('phone', $user->phone) }}" maxlength="20" placeholder="Não informado">
                </div>
                <div class="col-md-6">
                    <label for="date_of_birth" class="form-label text-muted">Data de nascimento</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                           value="{{ old('date_of_birth', $client?->date_of_birth) }}"
                           max="{{ now()->toDateString() }}">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-2"></i> Salvar dados
                </button>
                <button type="button" class="btn btn-secondary" id="btnCancelarPessoais">Cancelar</button>
            </div>
        </form>

        <hr class="my-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <button type="button" class="btn btn-danger"
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
        const btnEditar = document.getElementById('btnEditarPessoais');
        const btnCancelar = document.getElementById('btnCancelarPessoais');
        const view = document.getElementById('pessoaisView');
        const form = document.getElementById('pessoaisForm');

        function abrir() {
            view.classList.add('d-none');
            form.classList.remove('d-none');
            btnEditar.classList.add('d-none');
        }

        if (btnEditar) btnEditar.addEventListener('click', abrir);

        // Cancelar recarrega a página, voltando aos dados reais salvos.
        if (btnCancelar) {
            btnCancelar.addEventListener('click', function () {
                window.location.href = '{{ route('client.profile.edit') }}';
            });
        }

        // Se houve erro de validação nos dados pessoais, já abre o formulário.
        @if ($errors->hasAny(['name', 'email', 'phone', 'date_of_birth']))
            abrir();
        @endif

        @if ($errors->has('current_password') || $errors->has('password'))
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTrocarSenha')).show();
        @endif

        @if ($errors->deleteAccount->any())
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExcluirConta')).show();
        @endif
    });
</script>

@endsection

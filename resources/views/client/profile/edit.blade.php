@extends('layouts.main')

@section('title', 'Meu perfil')

@section('content')

<div class="dashboard-container container-fluid py-4">

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('dashboard') }}" class="btn btn-dark btn-sm">
            ← Voltar ao painel
        </a>
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Meu perfil</h1>
        <p class="dashboard-subtitle mb-0">Gerencie seus dados e sua senha</p>
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

    <div class="row g-4">

        {{-- Dados pessoais --}}
        <div class="col-lg-6">
            <div class="dashboard-box h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="section-title mb-0" style="font-size: 1.4rem;">Dados pessoais</h2>
                    <button type="button" class="btn btn-warning btn-sm" id="btnEditarPerfil">
                        <i class="bi bi-pencil me-1"></i> Editar
                    </button>
                </div>

                {{-- Modo leitura --}}
                <div id="perfilView">
                    <p class="mb-2"><span class="text-muted">Nome:</span> <strong>{{ $user->name }}</strong></p>
                    <p class="mb-2"><span class="text-muted">E-mail:</span> <strong>{{ $user->email }}</strong></p>
                    <p class="mb-0"><span class="text-muted">Telefone:</span> <strong>{{ $user->phone ?: '—' }}</strong></p>
                </div>

                {{-- Modo edição --}}
                <form method="POST" action="{{ route('client.profile.update') }}" id="perfilForm" class="d-none">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Telefone / WhatsApp</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $user->phone) }}"
                               placeholder="(11) 91234-5678">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-check-circle me-2"></i> Salvar dados
                        </button>
                        <button type="button" class="btn btn-secondary" id="btnCancelarPerfil">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Alterar senha --}}
        <div class="col-lg-6">
            <div class="dashboard-box h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="section-title mb-0" style="font-size: 1.4rem;">Alterar senha</h2>
                    <button type="button" class="btn btn-warning btn-sm" id="btnEditarSenha">
                        <i class="bi bi-pencil me-1"></i> Alterar
                    </button>
                </div>

                {{-- Modo leitura --}}
                <div id="senhaView">
                    <p class="mb-0"><span class="text-muted">Senha:</span> <strong>••••••••</strong></p>
                </div>

                {{-- Modo edição --}}
                <form method="POST" action="{{ route('client.profile.password') }}" id="senhaForm" class="d-none">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Senha atual</label>
                        <input type="password" name="current_password" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nova senha</label>
                        <input type="password" name="password" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmar nova senha</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-shield-lock me-2"></i> Alterar senha
                        </button>
                        <button type="button" class="btn btn-secondary" id="btnCancelarSenha">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnEditar = document.getElementById('btnEditarPerfil');
        const btnCancelar = document.getElementById('btnCancelarPerfil');
        const view = document.getElementById('perfilView');
        const form = document.getElementById('perfilForm');

        function abrir() {
            view.classList.add('d-none');
            form.classList.remove('d-none');
            btnEditar.classList.add('d-none');
        }

        function fechar() {
            form.classList.add('d-none');
            view.classList.remove('d-none');
            btnEditar.classList.remove('d-none');
        }

        if (btnEditar) btnEditar.addEventListener('click', abrir);
        if (btnCancelar) btnCancelar.addEventListener('click', fechar);

        // Se a validação dos dados falhou, já abre em modo edição.
        @if ($errors->has('name') || $errors->has('email') || $errors->has('phone'))
            abrir();
        @endif

        // --- Alterar senha ---
        const btnEditarSenha = document.getElementById('btnEditarSenha');
        const btnCancelarSenha = document.getElementById('btnCancelarSenha');
        const senhaView = document.getElementById('senhaView');
        const senhaForm = document.getElementById('senhaForm');

        function abrirSenha() {
            senhaView.classList.add('d-none');
            senhaForm.classList.remove('d-none');
            btnEditarSenha.classList.add('d-none');
        }

        function fecharSenha() {
            senhaForm.classList.add('d-none');
            senhaView.classList.remove('d-none');
            btnEditarSenha.classList.remove('d-none');
            senhaForm.reset();
        }

        if (btnEditarSenha) btnEditarSenha.addEventListener('click', abrirSenha);
        if (btnCancelarSenha) btnCancelarSenha.addEventListener('click', fecharSenha);

        // Se a validação da senha falhou, já abre em modo edição.
        @if ($errors->has('current_password') || $errors->has('password'))
            abrirSenha();
        @endif
    });
</script>

@endsection

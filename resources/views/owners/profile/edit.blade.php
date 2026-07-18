@extends('layouts.main')

@section('title', 'Minha Conta')

@section('content')

<div class="container py-4">

    <x-back :href="route('owners.dashboard')" />

    <h1 class="fw-bold mb-1">Minha Conta</h1>
    <p class="text-muted">Gerencie seus dados pessoais, da empresa e sua senha.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Não foi possível salvar.</strong> Corrija o que está marcado abaixo:
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">

        {{-- Dados pessoais --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Dados pessoais</h5>
                        <button type="button" class="btn btn-sm btn-warning" id="btnEditarPessoais">
                            ✏️ Editar
                        </button>
                    </div>

                    <div id="pessoaisView">
                        <p class="mb-1"><strong>Nome:</strong> {{ $user->name }}</p>
                        <p class="mb-1"><strong>E-mail:</strong> {{ $user->email }}</p>
                        <p class="mb-0"><strong>Telefone:</strong> {{ $user->phone ?: '—' }}</p>
                    </div>

                    <form method="POST" action="{{ route('owner.profile.personal') }}" id="pessoaisForm" class="d-none">
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
                            <label class="form-label">Telefone</label>
                            <input type="text" name="phone" class="form-control"
                                   value="{{ old('phone', $user->phone) }}" placeholder="(11) 91234-5678">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-sm">Salvar</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarPessoais">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Empresa --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Empresa</h5>
                        <button type="button" class="btn btn-sm btn-warning" id="btnEditarEmpresa">
                            ✏️ Editar
                        </button>
                    </div>

                    <div id="empresaView">
                        <p class="mb-1"><strong>Nome da empresa:</strong> {{ $owner->company_name }}</p>
                        <p class="mb-0"><strong>CPF/CNPJ:</strong> {{ $owner->tax_id }}</p>
                    </div>

                    <form method="POST" action="{{ route('owner.profile.company') }}" id="empresaForm" class="d-none">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label">Nome da empresa</label>
                            <input type="text" name="company_name" class="form-control"
                                   value="{{ old('company_name', $owner->company_name) }}" maxlength="150" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CPF / CNPJ</label>
                            <input type="text" name="tax_id" class="form-control"
                                   value="{{ old('tax_id', $owner->tax_id) }}"
                                   placeholder="Só números (11 ou 14 dígitos)" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-sm">Salvar</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarEmpresa">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Alterar senha --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Alterar senha</h5>
                        <button type="button" class="btn btn-sm btn-warning" id="btnEditarSenha">
                            ✏️ Alterar
                        </button>
                    </div>

                    <div id="senhaView">
                        <p class="mb-0"><strong>Senha:</strong> ••••••••</p>
                    </div>

                    <form method="POST" action="{{ route('owner.profile.password') }}" id="senhaForm" class="d-none">
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
                            <button type="submit" class="btn btn-success btn-sm">Alterar senha</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarSenha">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Encerrar conta --}}
        <div class="col-12">
            <div class="card shadow-sm border-0 border-danger-subtle">
                <div class="card-body">
                    <h5 class="fw-bold mb-1 text-danger">Encerrar conta</h5>
                    <p class="text-muted mb-3">
                        Encerra a sua conta e a empresa. O histórico das suas arenas — reservas,
                        pagamentos e caixa — <strong>é preservado</strong>. Só é possível encerrar
                        com <strong>nenhuma arena ativa</strong>: desative ou exclua todas antes.
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

                    <button type="button" class="btn btn-danger"
                            data-bs-toggle="modal" data-bs-target="#modalEncerrarConta">
                        <i class="bi bi-trash me-2"></i> Encerrar conta
                    </button>
                </div>
            </div>
        </div>

    </div>

</div>

<div class="modal fade" id="modalEncerrarConta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('owner.profile.destroy') }}">
                @csrf
                @method('DELETE')

                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i> Encerrar conta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger mb-3">
                        Sua conta e sua empresa serão encerradas, e os
                        <strong>funcionários das suas arenas perderão o acesso</strong>.
                    </div>

                    <p class="text-muted">
                        O histórico continua guardado. O seu e-mail, o nome da empresa e o
                        CPF/CNPJ ficam liberados para um novo cadastro no futuro — que será
                        uma empresa nova, começando do zero.
                    </p>

                    <label for="delete_password" class="form-label">Digite sua senha para confirmar</label>
                    <input type="password" id="delete_password" name="delete_password"
                           class="form-control" autocomplete="current-password" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i> Encerrar conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $abrirSecoes = [
        'pessoais' => $errors->hasAny(['name', 'email', 'phone']),
        'empresa'  => $errors->hasAny(['company_name', 'tax_id']),
        'senha'    => $errors->hasAny(['current_password', 'password']),
    ];
@endphp

<script>
    const ABRIR = @json($abrirSecoes);

    document.addEventListener('DOMContentLoaded', function () {
        function secao(btnEditarId, btnCancelarId, viewId, formId, abrirAuto) {
            const btnEditar = document.getElementById(btnEditarId);
            const btnCancelar = document.getElementById(btnCancelarId);
            const view = document.getElementById(viewId);
            const form = document.getElementById(formId);

            function abrir() {
                view.classList.add('d-none');
                form.classList.remove('d-none');
                btnEditar.classList.add('d-none');
            }

            if (btnEditar) btnEditar.addEventListener('click', abrir);

            // Cancelar recarrega a página, voltando aos dados reais salvos.
            if (btnCancelar) {
                btnCancelar.addEventListener('click', function () {
                    window.location.href = '{{ route('owner.profile.edit') }}';
                });
            }

            if (abrirAuto) abrir();
        }

        secao('btnEditarPessoais', 'btnCancelarPessoais', 'pessoaisView', 'pessoaisForm', ABRIR.pessoais);
        secao('btnEditarEmpresa', 'btnCancelarEmpresa', 'empresaView', 'empresaForm', ABRIR.empresa);
        secao('btnEditarSenha', 'btnCancelarSenha', 'senhaView', 'senhaForm', ABRIR.senha);
    });
</script>

@endsection
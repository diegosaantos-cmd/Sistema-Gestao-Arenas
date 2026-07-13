@extends('layouts.main')

@section('title', 'Minha Conta')

@section('content')

<div class="container py-4">

    <x-back :href="\App\Support\ArenaAtual::ehGerente() ? route('owners.dashboard') : route('employees.dashboard')" />

    <h1 class="fw-bold mb-1">Minha Conta</h1>
    <p class="text-muted">Gerencie seus dados pessoais e sua senha.</p>

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
                    @php $soConsulta = \App\Support\ArenaAtual::ehAtendente(); @endphp

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Dados pessoais</h5>
                        @unless ($soConsulta)
                            <button type="button" class="btn btn-sm btn-warning" id="btnEditarPessoais">
                                ✏️ Editar
                            </button>
                        @endunless
                    </div>

                    <div id="pessoaisView">
                        <p class="mb-1"><strong>Nome:</strong> {{ $user->name }}</p>
                        <p class="mb-1"><strong>E-mail:</strong> {{ $user->email }}</p>
                        <p class="mb-0"><strong>Telefone:</strong> {{ $user->phone ?: '—' }}</p>
                    </div>

                    @unless ($soConsulta)
                    <form method="POST" action="{{ route('employee.profile.personal') }}" id="pessoaisForm" class="d-none">
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
                    @else
                        <p class="text-muted small mt-3 mb-0">
                            Seus dados são definidos pelo proprietário. Se algo estiver errado, avise-o.
                        </p>
                    @endunless
                </div>
            </div>
        </div>

        {{-- Vínculo (somente leitura): arena e cargo são definidos pelo dono --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Meu vínculo</h5>

                    <p class="mb-1"><strong>Arena:</strong> {{ $employee->arena?->name ?? '—' }}</p>
                    <p class="mb-1"><strong>Cargo:</strong> {{ $employee->position ?: '—' }}</p>
                    <p class="mb-0">
                        <strong>Nível de acesso:</strong>
                        {{ $employee->access_level === 'managerial' ? 'Gerente' : 'Atendente' }}
                    </p>

                    <p class="text-muted small mt-3 mb-0">
                        A arena e o cargo são definidos pelo proprietário.
                    </p>
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

                    <form method="POST" action="{{ route('employee.profile.password') }}" id="senhaForm" class="d-none">
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

    </div>

</div>

@php
    $abrirSecoes = [
        'pessoais' => $errors->hasAny(['name', 'email', 'phone']),
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
                    window.location.href = '{{ route('employee.profile.edit') }}';
                });
            }

            if (abrirAuto) abrir();
        }

        secao('btnEditarPessoais', 'btnCancelarPessoais', 'pessoaisView', 'pessoaisForm', ABRIR.pessoais);
        secao('btnEditarSenha', 'btnCancelarSenha', 'senhaView', 'senhaForm', ABRIR.senha);
    });
</script>

@endsection

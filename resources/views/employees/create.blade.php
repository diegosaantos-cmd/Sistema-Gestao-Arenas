@extends('layouts.main')

@section('title', 'Novo Funcionário')

@section('content')

<div class="container py-5">

    <div class="card shadow mx-auto" style="max-width: 700px;">
        <div class="card-body p-4">

            <h1 class="h3 fw-bold mb-1">Novo funcionário</h1>
            <p class="text-muted">Crie o acesso de quem vai atender na arena.</p>

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

            <div class="alert alert-light border">
                <i class="bi bi-person-plus me-1"></i>
                Cadastrando funcionário na arena <strong>{{ $arena->name }}</strong>
            </div>

            <form action="{{ route('employees.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="name">Nome completo</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="{{ old('name') }}" required autocomplete="name">
                    </div>

                    <div class="col-12 col-md-7">
                        <label class="form-label" for="email">E-mail <span class="text-muted fw-normal">(usado para entrar)</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="{{ old('email') }}" required autocomplete="username">
                    </div>

                    <div class="col-12 col-md-5">
                        <label class="form-label" for="phone">
                            Telefone <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <input type="text" class="form-control" id="phone" name="phone"
                               value="{{ old('phone') }}" data-mask="telefone" inputmode="numeric"
                               placeholder="(11) 91234-5678" maxlength="20" autocomplete="tel">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="password">Senha</label>
                        <input type="password" class="form-control" id="password" name="password"
                               minlength="8" required autocomplete="new-password">
                        <div class="form-text">Mínimo de 8 caracteres.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="password_confirmation">Confirmar senha</label>
                        <input type="password" class="form-control" id="password_confirmation"
                               name="password_confirmation" minlength="8" required autocomplete="new-password">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="position">Cargo</label>
                        <input type="text" class="form-control" id="position" name="position"
                               value="{{ old('position') }}" placeholder="Ex.: Atendente, Gerente" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="access_level">Nível de acesso</label>
                        <select name="access_level" id="access_level" class="form-select min-w-0" required>
                            <option value="basic" {{ old('access_level') === 'basic' ? 'selected' : '' }}>
                                Funcionário (acesso básico)
                            </option>
                            <option value="managerial" {{ old('access_level') === 'managerial' ? 'selected' : '' }}>
                                Administrador (acesso gerencial)
                            </option>
                        </select>
                    </div>
                </div>

                <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-4">
                    <a href="{{ route('owners.dashboard') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Salvar
                    </button>
                </div>

            </form>

            <script src="/js/masks.js" defer></script>

        </div>
    </div>

</div>

@endsection

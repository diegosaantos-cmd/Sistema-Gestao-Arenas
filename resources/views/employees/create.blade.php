@extends('layouts.main')

@section('title', 'Novo Funcionário')

@section('content')

<div class="container py-5">

    <div class="card shadow mx-auto" style="max-width: 700px;">
        <div class="card-body p-4">

            <h2 class="mb-4">Novo Funcionário</h2>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="fs-5 mb-3">
                👤 Cadastrando funcionário na arena:
                <strong>{{ $arena->name }}</strong>
            </p>

            <form action="{{ route('employees.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <input type="text" name="name" class="form-control"
                           placeholder="Nome completo" value="{{ old('name') }}" required>
                </div>

                <div class="row">
                    <div class="col-md-7 mb-3">
                        <input type="email" name="email" class="form-control"
                               placeholder="E-mail (login)" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-md-5 mb-3">
                        <input type="text" name="phone" class="form-control"
                               placeholder="Telefone" value="{{ old('phone') }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input type="password" name="password" class="form-control"
                               placeholder="Senha (mín. 8)" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Confirmar senha" required>
                    </div>
                </div>

                <div class="mb-3">
                    <input type="text" name="position" class="form-control"
                           placeholder="Cargo (ex.: Atendente, Gerente)" value="{{ old('position') }}" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Tipo de funcionário</label>
                    <select name="access_level" class="form-select" required>
                        <option value="basic" {{ old('access_level') === 'basic' ? 'selected' : '' }}>
                            Funcionário (acesso básico)
                        </option>
                        <option value="managerial" {{ old('access_level') === 'managerial' ? 'selected' : '' }}>
                            Administrador (acesso gerencial)
                        </option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('owners.dashboard') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Salvar
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

@endsection

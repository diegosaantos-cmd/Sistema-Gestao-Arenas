@extends('layouts.main')

@section('title', 'Editar Funcionário')

@section('content')

<div class="container py-5">

    <div class="card shadow mx-auto" style="max-width: 700px;">
        <div class="card-body p-4">

            <h2 class="mb-1">Editar Funcionário</h2>
            <p class="text-muted">Arena: <strong>{{ $arena->name }}</strong></p>

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

            <form action="{{ route('employees.update', $employee) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nome completo</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $employee->user->name) }}" required>
                </div>

                <div class="row">
                    <div class="col-md-7 mb-3">
                        <label class="form-label">E-mail (login)</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email', $employee->user->email) }}" required>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $employee->user->phone) }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nova senha</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Deixe em branco para manter">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar nova senha</label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Repita a nova senha">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cargo</label>
                    <input type="text" name="position" class="form-control"
                           value="{{ old('position', $employee->position) }}" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Tipo de funcionário</label>
                    @php $nivel = old('access_level', $employee->access_level); @endphp
                    <select name="access_level" class="form-select" required>
                        <option value="basic" {{ $nivel === 'basic' ? 'selected' : '' }}>
                            Funcionário (acesso básico)
                        </option>
                        <option value="managerial" {{ $nivel === 'managerial' ? 'selected' : '' }}>
                            Administrador (acesso gerencial)
                        </option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>

            </form>

        </div>
    </div>

</div>

@endsection

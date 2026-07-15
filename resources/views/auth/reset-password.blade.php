@extends('layouts.main')

@section('title', 'Redefinir senha')

@section('content')
<div class="container py-5">
    <div class="card shadow border-0 mx-auto" style="max-width: 460px;">
        <div class="card-body p-4 p-md-5">

            <div class="position-relative text-center mb-4">
                <a href="{{ url('/') }}" class="logo-marca">ArenaPlay</a>

                <a href="{{ route('login') }}" class="btn-close position-absolute top-0 end-0"
                   aria-label="Fechar" title="Fechar"></a>
            </div>

            <div class="text-center mb-4">
                <h1 class="h3 fw-bold mb-1">Redefinir senha</h1>
                <p class="text-muted mb-0">Crie uma nova senha para sua conta.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Nova senha</label>
                    <input type="password" class="form-control" id="password" name="password"
                           minlength="8" required autocomplete="new-password">
                    <div class="form-text">Mínimo de 8 caracteres.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirmar nova senha</label>
                    <input type="password" class="form-control" id="password_confirmation"
                           name="password_confirmation" minlength="8" required autocomplete="new-password">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Redefinir senha
                    </button>
                </div>

                <hr class="my-4">

                <p class="text-center small text-muted mb-0">
                    <a href="{{ route('login') }}">Voltar para entrar</a>
                </p>
            </form>
        </div>
    </div>
</div>
@endsection

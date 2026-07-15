@extends('layouts.main')

@section('title', 'Entrar')

@section('content')
<div class="container py-5">
    <div class="card shadow border-0 mx-auto" style="max-width: 460px;">
        <div class="card-body p-4 p-md-5">

            <div class="position-relative text-center mb-4">
                <a href="{{ url('/') }}" class="logo-marca">ArenaPlay</a>

                <a href="{{ url('/') }}" class="btn-close position-absolute top-0 end-0"
                   aria-label="Fechar" title="Fechar"></a>
            </div>

            <div class="text-center mb-4">
                <h1 class="h3 fw-bold mb-1">Entrar</h1>
                <p class="text-muted mb-0">Acesse sua conta para gerenciar suas reservas.</p>
            </div>

            @session('status')
                <div class="alert alert-success">{{ $value }}</div>
            @endsession

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="{{ old('email') }}" required autofocus autocomplete="username">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Senha</label>
                    <input type="password" class="form-control" id="password" name="password"
                           required autocomplete="current-password">
                </div>

                <div class="d-flex justify-content-end mb-4">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="small text-decoration-none">
                            Esqueceu a senha?
                        </a>
                    @endif
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                    </button>
                </div>

                <hr class="my-4">

                <p class="text-center small text-muted mb-1">
                    Não tem conta? <a href="{{ route('register') }}">Criar conta</a>
                </p>
                <p class="text-center small text-muted mb-0">
                    Tem uma arena? <a href="{{ route('register.arena.owners') }}">Cadastre sua arena</a>
                </p>
            </form>
        </div>
    </div>
</div>
@endsection

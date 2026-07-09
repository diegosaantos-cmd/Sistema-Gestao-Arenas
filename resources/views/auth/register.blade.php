@extends('layouts.main')

@section('title', 'Criar conta')

@section('content')
<div class="container py-5">
    <div class="card shadow border-0 mx-auto" style="max-width: 560px;">
        <div class="card-body p-4 p-md-5">

            <div class="position-relative text-center mb-4">
                <a href="{{ url('/') }}" class="logo-marca">ArenaPlay</a>

                <a href="{{ url('/') }}" class="btn-close position-absolute top-0 end-0"
                   aria-label="Fechar" title="Fechar"></a>
            </div>

            <div class="text-center mb-4">
                <h1 class="h3 fw-bold mb-1">Criar conta</h1>
                <p class="text-muted mb-0">Reserve quadras nas melhores arenas.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Não foi possível criar a conta.</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="name">Nome completo</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="{{ old('name') }}" maxlength="255" required autofocus autocomplete="name">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="email">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="{{ old('email') }}" maxlength="255" required autocomplete="username">
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="phone">Telefone</label>
                        <input type="text" class="form-control" id="phone" name="phone"
                               value="{{ old('phone') }}" data-mask="telefone" inputmode="numeric"
                               placeholder="(11) 91234-5678" maxlength="20" required autocomplete="tel">
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="date_of_birth">
                            Data de nascimento <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                               value="{{ old('date_of_birth') }}" max="{{ now()->toDateString() }}">
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="password">Senha</label>
                        <input type="password" class="form-control" id="password" name="password"
                               minlength="8" required autocomplete="new-password">
                        <div class="form-text">Mínimo de 8 caracteres.</div>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="password_confirmation">Confirmar senha</label>
                        <input type="password" class="form-control" id="password_confirmation"
                               name="password_confirmation" minlength="8" required autocomplete="new-password">
                    </div>
                </div>

                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="terms" id="terms" value="1"
                               {{ old('terms') ? 'checked' : '' }} required>
                        <label class="form-check-label" for="terms">
                            Li e aceito os
                            <a href="{{ route('terms.show') }}" target="_blank">termos de uso</a>
                            e a <a href="{{ route('policy.show') }}" target="_blank">política de privacidade</a>.
                        </label>
                    </div>
                @endif

                <div class="d-grid gap-2 d-sm-flex justify-content-sm-between align-items-sm-center mt-4">
                    <a href="{{ route('login') }}" class="small text-decoration-none">Já tem conta? Entrar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Criar conta
                    </button>
                </div>

                <hr class="my-4">

                <p class="text-center small text-muted mb-0">
                    Tem uma arena?
                    <a href="{{ route('register.arena.owners') }}">Cadastre sua arena aqui.</a>
                </p>
            </form>
        </div>
    </div>
</div>

<script src="/js/masks.js" defer></script>
@endsection

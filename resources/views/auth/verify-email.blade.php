@extends('layouts.main')

@section('title', 'Confirme seu e-mail')

@section('content')
<div class="container py-5">
    <div class="card shadow border-0 mx-auto" style="max-width: 460px;">
        <div class="card-body p-4 p-md-5">

            <div class="position-relative text-center mb-4">
                <a href="{{ url('/') }}" class="logo-marca">ArenaPlay</a>
            </div>

            <div class="text-center mb-4">
                <h1 class="h3 fw-bold mb-1">Confirme seu e-mail</h1>
                <p class="text-muted mb-0">
                    Enviamos um link de confirmação para o seu e-mail. Clique nele para
                    ativar sua conta. Não recebeu? Podemos enviar de novo.
                </p>
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success">
                    Enviamos um novo link de confirmação para o seu e-mail.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-envelope me-1"></i> Reenviar e-mail de confirmação
                    </button>
                </div>
            </form>

            <hr class="my-4">

            <div class="text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right me-1"></i> Sair
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

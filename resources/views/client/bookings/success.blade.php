@extends('layouts.main')

@section('title', 'Reserva confirmada')

@section('content')

<div class="dashboard-container container-fluid py-4">

    <div class="dashboard-box text-center mx-auto" style="max-width: 560px;">

        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>

        <h1 class="section-title mt-3">Reserva enviada!</h1>

        <p class="text-muted">
            Sua reserva foi registrada e está <strong>pendente</strong> de confirmação pela arena.
            Você pode acompanhá-la em "Minhas reservas".
        </p>

        <div class="d-grid gap-3 mt-4">
            <a href="{{ route('client.arenas.index') }}" class="btn dashboard-btn-primary">
                <i class="bi bi-calendar-plus me-2"></i> Fazer outra reserva
            </a>
            <a href="{{ route('dashboard') }}" class="btn dashboard-btn-outline">
                <i class="bi bi-house me-2"></i> Voltar ao painel
            </a>
        </div>

    </div>

</div>

@endsection

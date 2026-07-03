@extends('layouts.main')

@section('title', 'Próximos agendamentos')

@section('content')

<div class="dashboard-container container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('dashboard') }}" class="btn btn-dark btn-sm">
            ← Voltar ao painel
        </a>

        <div class="d-flex gap-2">
            <a href="{{ route('client.bookings.history') }}" class="btn btn-dark btn-sm">
                Ver histórico
            </a>
            <a href="{{ route('client.arenas.index') }}" class="btn btn-primary btn-sm">
                + Nova reserva
            </a>
        </div>
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">{{ $titulo ?? 'Próximos agendamentos' }}</h1>
        <p class="dashboard-subtitle mb-0">{{ $subtitulo ?? 'Suas reservas pendentes e confirmadas' }}</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $badges = [
            'pending'   => ['Pendente', 'bg-warning text-dark'],
            'confirmed' => ['Confirmada', 'bg-success'],
            'cancelled' => ['Cancelada', 'bg-danger'],
            'completed' => ['Concluída', 'bg-success'],
        ];
        $dias = [
            0 => 'Dom', 1 => 'Seg', 2 => 'Ter', 3 => 'Qua',
            4 => 'Qui', 5 => 'Sex', 6 => 'Sáb',
        ];
    @endphp

    <div class="row g-4">
        @forelse ($proximas as $b)
            @include('client.bookings._card', ['b' => $b, 'badges' => $badges, 'dias' => $dias])
        @empty
            <div class="col-12">
                <div class="dashboard-box text-center text-muted">
                    {{ $mensagemVazia ?? 'Você não tem agendamentos próximos.' }}
                    <div class="mt-3">
                        <a href="{{ route('client.arenas.index') }}" class="btn dashboard-btn-primary">
                            <i class="bi bi-calendar-plus me-2"></i> Nova reserva
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

</div>

@endsection

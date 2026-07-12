@extends('layouts.main')

@section('title', 'Reservas — ' . $arena->name)

@section('content')
@php
    $statusReservas = [
        'pending'   => ['Pendente',   'bg-warning text-dark'],
        'confirmed' => ['Confirmada', 'bg-success'],
        'completed' => ['Concluída',  'bg-success'],
        'cancelled' => ['Cancelada',  'bg-danger'],
    ];
@endphp

<div class="dashboard-container container-fluid py-4">
    <x-back :href="route('admin.arenas.show', [$arena, 'origem' => request('origem')])" />

    <div class="mb-4">
        <div class="text-muted text-uppercase fw-semibold small" style="letter-spacing:.05em;">Arena {{ $arena->name }}</div>
        <h1 class="dashboard-title mb-1">Reservas da arena</h1>
        <p class="dashboard-subtitle mb-0">Reservas do mês, canceladas e histórico completo.</p>
    </div>

    <form method="GET" class="mb-3 d-flex gap-2 flex-wrap" style="max-width: 520px;">
        <input type="hidden" name="origem" value="{{ request('origem') }}">
        <input type="hidden" name="aba_reservas" value="{{ $aba }}">
        <input type="text" name="busca_reserva" value="{{ $busca }}"
               class="form-control" placeholder="Nome do cliente ou data (dd/mm/aaaa)">
        <button class="btn btn-primary">Buscar</button>
        @if ($busca)
            <a href="{{ route('admin.arenas.reservas', [$arena, 'origem' => request('origem')]) }}" class="btn btn-outline-secondary">Limpar</a>
        @endif
    </form>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button class="nav-link {{ $aba === 'canceladas' ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#abaMes" type="button">
                Reservas do mês <span class="badge bg-secondary ms-1">{{ $reservasMesLista->total() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-danger {{ $aba === 'canceladas' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#abaCanceladas" type="button">
                Canceladas <span class="badge bg-danger ms-1">{{ $reservasCanceladasLista->total() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $aba === 'canceladas' ? '' : 'show active' }}" id="abaMes">
            @include('admin.arenas._booking-list', ['listaReservas' => $reservasMesLista])
        </div>
        <div class="tab-pane fade {{ $aba === 'canceladas' ? 'show active' : '' }}" id="abaCanceladas">
            @include('admin.arenas._booking-list', ['listaReservas' => $reservasCanceladasLista])
        </div>
    </div>
</div>
@endsection

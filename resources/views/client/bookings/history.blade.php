@extends('layouts.main')

@section('title', 'Histórico de reservas')

@section('content')

<div class="dashboard-container container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <x-back :href="route('dashboard')" class="mb-0" />
        <a href="{{ route('client.bookings.index') }}" class="btn btn-dark btn-sm">
            Ver próximos
        </a>
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Histórico</h1>
        <p class="dashboard-subtitle mb-0">Reservas canceladas e realizadas</p>
    </div>

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
        @forelse ($historico as $b)
            @include('client.bookings._card', ['b' => $b, 'badges' => $badges, 'dias' => $dias])
        @empty
            <div class="col-12">
                <div class="dashboard-box text-center text-muted">
                    Nenhuma reserva no histórico ainda.
                </div>
            </div>
        @endforelse
    </div>

    @if ($historico instanceof \Illuminate\Contracts\Pagination\Paginator && $historico->hasPages())
        <div class="mt-4">{{ $historico->links() }}</div>
    @endif

</div>

@endsection

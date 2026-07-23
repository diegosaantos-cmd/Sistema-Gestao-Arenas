@extends('layouts.main')

@section('title', 'Nova Reserva')

@section('content')

<div class="dashboard-container container-fluid py-4">

    <div class="d-flex flex-wrap gap-2 mb-3">
        <x-back :href="route('dashboard')" class="mb-0" />
    </div>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Nova Reserva</h1>
        <p class="dashboard-subtitle mb-0">Escolha uma arena para ver as quadras e reservar.</p>
    </div>

    <form method="GET" action="{{ route('client.arenas.index') }}"
          class="arena-search-form arena-search-box shadow-sm mb-4" data-update-url="true">
        <div class="input-group">
            <input type="search" id="buscaArena" name="busca" class="form-control border-end-0"
                   value="{{ $busca }}" placeholder="Nome da arena…">
            <button type="submit" class="btn bg-white text-secondary border border-start-0"
                    style="border-color: var(--bs-border-color) !important;"
                    aria-label="Pesquisar" title="Pesquisar">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="row g-4" data-arena-results>
        @forelse ($arenas as $arena)
            <div class="col-6 col-lg-3">
                @include('client.arenas._gallery-card', [
                    'arenaUrl' => route('client.arenas.show', [$arena, 'origem' => 'lista']),
                    'botaoTexto' => 'Ver arena',
                    'favoritasIds' => $favoritasIds ?? [],
                ])
            </div>
        @empty
            <div class="col-12">
                <div class="dashboard-box text-center text-muted">
                    {{ $busca !== ''
                        ? 'Nenhuma arena encontrada para essa pesquisa.'
                        : 'Nenhuma arena disponível no momento.' }}
                </div>
            </div>
        @endforelse
    </div>

    @if ($arenas->hasPages())
        <div class="mt-4">{{ $arenas->links() }}</div>
    @endif

</div>

@include('client.arenas._live-search')

@endsection

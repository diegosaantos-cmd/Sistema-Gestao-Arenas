@extends('layouts.main')

@section('title', 'Minhas arenas favoritas')

@section('content')
<div class="dashboard-container container-fluid py-4">

    <a href="{{ route('dashboard') }}" class="btn btn-dark btn-sm mb-3">← Voltar ao painel</a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">
            <i class="bi bi-heart-fill text-danger me-2"></i>Minhas arenas favoritas
        </h1>
        <p class="dashboard-subtitle mb-0">
            {{ $arenas->count() }}
            {{ $arenas->count() === 1 ? 'arena favoritada' : 'arenas favoritadas' }}
        </p>
    </div>

    @if ($arenas->isEmpty())
        <div class="dashboard-box text-center py-5">
            <i class="bi bi-heart display-4 text-secondary d-block mb-3"></i>
            <h2 class="h5 fw-bold">Você ainda não favoritou nenhuma arena</h2>
            <p class="text-muted mb-0">
                Ao navegar pelas arenas, clique no <i class="bi bi-heart"></i> para guardá-las aqui
                e encontrá-las rapidamente depois.
            </p>
        </div>
    @else
        <div class="row g-4">
            @foreach ($arenas as $arena)
                <div class="col-6 col-lg-3" data-favorite-card>
                    @include('client.arenas._gallery-card', [
                        'arenaUrl' => route('client.arenas.show', $arena),
                        'botaoTexto' => 'Ver arena',
                        'favoritasIds' => $favoritasIds,
                        'removerAoDesfavoritar' => true,
                    ])
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection

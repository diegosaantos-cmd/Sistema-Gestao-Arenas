@extends('layouts.main')
@section('title', 'ArenaPlay')
@section('content')
      <div id="carouselExample" class="carousel slide">
  <div class="carousel-inner">
    <img src="{{ asset('img/img1.jpg') }}" 

                 class="d-block w-100"

                 style="height: 500px; object-fit: cover;">

            <div class="carousel-caption d-none d-md-block">

                <h1>Bem-vindo à ArenaPlay</h1>

                <p>Os melhores jogos e campeonatos.</p>

            </div>
    <div class="carousel-item">
      <img src="{{ asset('img/img2.jpg') }}" class="d-block w-100" alt="...">
    </div>
    <div class="carousel-item">
      <img src="{{ asset('img/img3.jpg') }}" class="d-block w-100" alt="...">
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
</div>
<div class="container py-5">

    <h2 class="fw-bold mb-4 text-center">
        Arenas Disponíveis
    </h2>

    <form method="GET" action="{{ url('/') }}"
          class="arena-search-form arena-search-box shadow-sm mb-4 mx-auto" style="max-width: 480px;"
          data-update-url="true">
        <div class="input-group">
            <input type="search" name="busca" class="form-control border-end-0"
                   value="{{ $busca }}"
                   placeholder="Pesquisar pelo nome da arena"
                   aria-label="Pesquisar arena">
            <button type="submit" class="btn bg-white text-secondary border border-start-0"
                    aria-label="Pesquisar" title="Pesquisar">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="row" data-arena-results>
        @forelse($arenas as $arena)

            <div class="col-6 col-lg-3 mb-4">
                @include('client.arenas._gallery-card', [
                    'arenaUrl' => auth()->check()
                        ? route('client.arenas.show', $arena)
                        : route('login'),
                    'botaoTexto' => 'Ver arena',
                ])
            </div>

        @empty

            <div class="col-12 text-center py-5">
                <h4>Nenhuma arena cadastrada ainda</h4>
            </div>

        @endforelse

    </div>

</div>

@include('client.arenas._live-search')
@endsection

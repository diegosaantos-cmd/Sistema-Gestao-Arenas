@extends('layouts.main')
@section('title', 'ArenaPlay')
@section('content')
{{--
    Carrossel do cabeçalho. As fotos e os textos vêm do banco (home_slides),
    gerenciados pelo admin em /admin/aparencia. Sem nenhuma foto cadastrada,
    cai no slide padrão do sistema.

    O `px-0` é necessário: o layout injeta o conteúdo dentro de um `.row`, e o
    Bootstrap dá 0.75rem de padding a todo filho direto de `.row` (`.row > *`).
    Sem anular isso, sobrava uma faixa branca de 12px em cada lado da foto.
--}}
<div id="carouselExample" class="carousel slide px-0" data-bs-ride="carousel">
    <div class="carousel-inner">
        @forelse ($slides as $slide)
            <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                <img src="{{ $slide->url() }}" class="d-block w-100"
                     style="height: 500px; object-fit: cover;"
                     alt="{{ $slide->titulo ?: 'ArenaPlay' }}">

                @if ($slide->titulo || $slide->subtitulo)
                    <div class="carousel-caption">
                        <div class="slide-legenda"
                             style="color: {{ $slide->corTexto() }}; background-color: {{ $slide->fundoLegenda() }};">
                            @if ($slide->titulo)
                                <h1>{{ $slide->titulo }}</h1>
                            @endif
                            @if ($slide->subtitulo)
                                <p>{{ $slide->subtitulo }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="carousel-item active">
                <img src="{{ asset(\App\Models\HomeSlide::IMAGEM_PADRAO) }}" class="d-block w-100"
                     style="height: 500px; object-fit: cover;" alt="ArenaPlay">
                <div class="carousel-caption">
                    <div class="slide-legenda" style="color: #FFFFFF; background-color: rgba(0, 0, 0, .45);">
                        <h1>Bem-vindo à ArenaPlay</h1>
                        <p>Os melhores jogos e campeonatos.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Setas só fazem sentido com mais de uma foto. --}}
    @if ($slides->count() > 1)
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Próxima</span>
        </button>
    @endif
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
                    'favoritasIds' => $favoritasIds ?? [],
                    'arenaUrl' => route('client.arenas.show', [$arena, 'origem' => 'inicio']),
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

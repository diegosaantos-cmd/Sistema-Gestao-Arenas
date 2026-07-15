@php
    // Só cliente logado favorita. Este card também é usado na tela inicial
    // pública, onde o visitante pode nem estar autenticado.
    $podeFavoritar = auth()->check() && auth()->user()->type === 'client';
    $ehFavorita = in_array($arena->id, $favoritasIds ?? [], true);
@endphp

<div class="card arena-card h-100 border-0 shadow-sm overflow-hidden position-relative">
    @php $fotosCard = $arena->relationLoaded('photos') ? $arena->photos->filter->arquivoExiste() : collect(); @endphp
    @if ($fotosCard->isNotEmpty())
        <div id="arenaCarousel{{ $arena->id }}" class="carousel slide">
            <div class="carousel-inner">
                @foreach ($fotosCard as $foto)
                    <div class="carousel-item @if ($loop->first) active @endif">
                        <img src="{{ $foto->url() }}" class="d-block w-100"
                             style="height: 130px; object-fit: cover;" alt="Foto de {{ $arena->name }}">
                    </div>
                @endforeach
            </div>
            @if ($fotosCard->count() > 1)
                <button class="carousel-control-prev" type="button"
                        data-bs-target="#arenaCarousel{{ $arena->id }}" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button"
                        data-bs-target="#arenaCarousel{{ $arena->id }}" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                    <span class="visually-hidden">Próxima</span>
                </button>
            @endif

            {{-- Abre as fotos em tela cheia (lightbox compartilhado). --}}
            <button type="button"
                    class="btn btn-dark btn-sm rounded-circle shadow-sm position-absolute bottom-0 start-0 m-2 opacity-75 d-flex align-items-center justify-content-center p-0"
                    style="width: 32px; height: 32px; z-index: 3;"
                    data-lightbox="{{ $fotosCard->map->url()->values()->toJson() }}"
                    data-lightbox-titulo="{{ $arena->name }}"
                    title="Ver fotos em tela cheia" aria-label="Ver fotos em tela cheia">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
        </div>
    @else
        {{-- Sem fotos: placeholder padrão (emoji de arena em fundo azul). --}}
        <div class="d-flex align-items-center justify-content-center"
             style="height: 130px; background: linear-gradient(135deg, #021B35, #0d6efd);">
            <span style="font-size: 3.5rem; line-height: 1;" role="img" aria-label="Arena">🏟️</span>
        </div>
    @endif

    @if ($podeFavoritar)
        {{-- Nas telas de navegação, favoritar é AJAX (não recarrega). Na página
             de Favoritos, sem data-favorite-form: envia normal e recarrega, para
             o contador e o estado vazio se atualizarem. --}}
        <form method="POST" action="{{ route('client.arenas.favoritar', $arena) }}"
              class="position-absolute top-0 end-0 m-2" style="z-index: 4;"
              {{ ($favoritoAjax ?? true) ? 'data-favorite-form' : '' }}>
            @csrf
            <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm"
                    data-fav-btn data-fav-style="card"
                    style="width: 36px; height: 36px;"
                    title="{{ $ehFavorita ? 'Remover das favoritas' : 'Adicionar às favoritas' }}"
                    aria-label="{{ $ehFavorita ? 'Remover das favoritas' : 'Adicionar às favoritas' }}">
                <i class="bi {{ $ehFavorita ? 'bi-heart-fill text-danger' : 'bi-heart text-secondary' }}"></i>
            </button>
        </form>
    @endif

    <div class="card-body d-flex flex-column">
        <h3 class="h5 fw-bold">{{ $arena->name }}</h3>

        <p class="small mb-2">
            <i class="bi bi-geo-alt me-1"></i>
            <span class="text-muted">Endereço:</span>
            {{ $arena->address_rua }}, {{ $arena->address_numero }}
            — {{ $arena->address_bairro }}
        </p>

        <p class="small mb-3">
            <i class="bi bi-grid-3x3-gap me-1"></i>
            {{ $arena->quadras_ativas_count ?? 0 }}
            {{ ($arena->quadras_ativas_count ?? 0) === 1 ? 'quadra disponível' : 'quadras disponíveis' }}
        </p>

        @if ($arena->description)
            <p class="card-text text-muted small">
                {{ \Illuminate\Support\Str::limit($arena->description, 100) }}
            </p>
        @endif

        <a href="{{ $arenaUrl }}" class="btn dashboard-btn-primary mt-auto">
            <i class="bi bi-eye me-2"></i> {{ $botaoTexto ?? 'Ver arena' }}
        </a>
    </div>
</div>

@once
    @include('client.arenas._photo-lightbox')
@endonce

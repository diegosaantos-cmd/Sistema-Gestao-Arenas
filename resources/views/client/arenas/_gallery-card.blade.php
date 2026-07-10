@php
    // Só cliente logado favorita. Este card também é usado na tela inicial
    // pública, onde o visitante pode nem estar autenticado.
    $podeFavoritar = auth()->check() && auth()->user()->type === 'client';
    $ehFavorita = in_array($arena->id, $favoritasIds ?? [], true);
@endphp

<div class="card h-100 border-0 shadow-sm overflow-hidden position-relative">
    <div class="d-flex align-items-center justify-content-center text-white"
         style="height: 130px; background: linear-gradient(135deg, #021B35, #0d6efd);">
        <i class="bi bi-building display-3"></i>
    </div>

    @if ($podeFavoritar)
        {{-- Nas telas de navegação, favoritar é AJAX (não recarrega). Na página
             de Favoritos, sem data-favorite-form: envia normal e recarrega, para
             o contador e o estado vazio se atualizarem. --}}
        <form method="POST" action="{{ route('client.arenas.favoritar', $arena) }}"
              class="position-absolute top-0 end-0 m-2"
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

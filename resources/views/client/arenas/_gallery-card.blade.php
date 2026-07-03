<div class="card h-100 border-0 shadow-sm overflow-hidden">
    <div class="d-flex align-items-center justify-content-center text-white"
         style="height: 130px; background: linear-gradient(135deg, #021B35, #0d6efd);">
        <i class="bi bi-building display-3"></i>
    </div>

    <div class="card-body d-flex flex-column">
        <h3 class="h5 fw-bold">{{ $arena->name }}</h3>

        <p class="small mb-2">
            <i class="bi bi-person-badge me-1"></i>
            <span class="text-muted">Responsável:</span>
            {{ $arena->owner?->company_name ?: ($arena->owner?->user?->name ?? 'Não informado') }}
        </p>

        <p class="text-muted small mb-2">
            <i class="bi bi-geo-alt me-1"></i>
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

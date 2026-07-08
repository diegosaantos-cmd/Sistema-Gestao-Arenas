@extends('layouts.main')

@section('title', 'Quadras do Sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm mb-3">← Voltar ao painel</a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Quadras cadastradas nas arenas</h1>
        <p class="dashboard-subtitle mb-0">Todas as quadras, sua respectiva arena e empresa.</p>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-md-6">
            <div class="input-group arena-search-box shadow-sm">
                <input type="search" class="form-control"
                       placeholder="Pesquise por nome da quadra, arena ou empresa"
                       aria-label="Pesquisar quadra"
                       data-court-search-input>
                <span class="input-group-text bg-white border-0">
                    <i class="bi bi-search text-secondary"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @forelse ($quadras as $quadra)
            <div class="col-6 col-lg-3 court-card-shell"
                 data-court-card
                 data-court-search="{{ $quadra->name }} {{ $quadra->arena?->name }} {{ $quadra->arena?->owner?->company_name }} {{ $quadra->arena?->owner?->user?->name }}">
                <div class="dashboard-box p-3 d-flex flex-column h-100 court-system-card">
                    <span class="badge bg-danger text-white align-self-start mb-2 px-2 py-0 fw-normal">Quadra</span>

                    <div class="mb-3">
                        <h2 class="h5 fw-bold mb-1">{{ $quadra->name }}</h2>
                        <span class="badge fw-normal {{ $quadra->active ? 'bg-success' : 'bg-warning text-dark' }}"
                              data-court-status>
                            {{ $quadra->active ? 'Ativa' : 'Desativada' }}
                        </span>

                        <div class="row g-3 mt-1">
                            <div class="col-6">
                                <span class="small text-dark fw-bold">Arena</span><br>
                                <span>{{ $quadra->arena?->name ?? '—' }}</span>
                            </div>
                            <div class="col-6">
                                <span class="small text-dark fw-bold">Empresa</span><br>
                                <span>{{ $quadra->arena?->owner?->company_name ?? '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto">
                        @if ($quadra->arena)
                            <a href="{{ route('admin.arenas.show', [$quadra->arena, 'origem' => 'quadras_sistema']) }}"
                               class="btn btn-primary btn-sm w-100">
                                Ver detalhes
                            </a>
                        @else
                            <span class="text-muted small">Arena excluída.</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="dashboard-box text-center text-muted">Nenhuma quadra cadastrada.</div>
            </div>
        @endforelse

        <div class="col-12 d-none" data-court-no-results>
            <div class="dashboard-box text-center text-muted">Nenhuma quadra encontrada.</div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.querySelector('[data-court-search-input]');
        const courtCards = document.querySelectorAll('[data-court-card]');
        const noResults = document.querySelector('[data-court-no-results]');

        function normalizeSearch(value) {
            return (value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, '')
                .toLowerCase();
        }

        searchInput.addEventListener('input', function () {
            const term = normalizeSearch(searchInput.value);
            let visibleCards = 0;

            courtCards.forEach(function (card) {
                const matches = normalizeSearch(card.dataset.courtSearch).includes(term);
                card.classList.toggle('d-none', !matches);

                if (matches) {
                    visibleCards++;
                }
            });

            noResults.classList.toggle('d-none', visibleCards > 0);
        });
    });
</script>
@endsection

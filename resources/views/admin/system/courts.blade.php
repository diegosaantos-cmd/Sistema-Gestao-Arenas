@extends('layouts.main')

@section('title', 'Quadras do Sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <x-back :href="route('admin.dashboard')" />

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Quadras cadastradas nas arenas</h1>
        <p class="dashboard-subtitle mb-0">Todas as quadras, sua respectiva arena e empresa.</p>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-md-6">
            <div class="input-group arena-search-box shadow-sm">
                <input type="search" class="form-control"
                       placeholder="Quadra, arena ou empresa…"
                       aria-label="Pesquisar quadra"
                       data-court-search-input>
                <span class="input-group-text bg-white border-0">
                    <i class="bi bi-search text-secondary"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        @forelse ($quadras as $quadra)
            <div class="col-12 col-sm-6 col-lg-3 court-card-shell"
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
                            {{-- Detalhes da própria quadra, abertos aqui mesmo (sem ir pra arena). --}}
                            <div class="collapse mb-3" id="detalhesQuadraSys{{ $quadra->id }}">
                                <div class="border-top pt-3">
                                    <div class="row g-3">
                                        @if ($quadra->description)
                                            <div class="col-12">
                                                <span class="small fw-bold text-dark">Descrição</span><br>
                                                <span class="small">{{ $quadra->description }}</span>
                                            </div>
                                        @endif
                                        <div class="col-6">
                                            <span class="small fw-bold text-dark">Arena</span><br>
                                            <span>{{ $quadra->arena->name }}</span>
                                        </div>
                                        @if ($quadra->created_at)
                                            <div class="col-6">
                                                <span class="small fw-bold text-dark">Cadastrada em</span><br>
                                                <span>{{ $quadra->created_at->format('d/m/Y H:i') }}</span>
                                            </div>
                                        @endif
                                        @if ($quadra->updated_at)
                                            <div class="col-6">
                                                <span class="small fw-bold text-dark">Última atualização</span><br>
                                                <span>{{ $quadra->updated_at->format('d/m/Y H:i') }}</span>
                                            </div>
                                        @endif
                                        @if ($quadra->sports->isNotEmpty())
                                            <div class="col-12">
                                                <span class="small fw-bold text-dark">Esportes</span><br>
                                                <span class="small">
                                                    {{ $quadra->sports
                                                        ->map(fn ($sport) => \App\Models\Court::SPORTS[$sport->sport] ?? $sport->sport)
                                                        ->implode(', ') }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="col-12">
                                            <span class="small fw-bold text-dark">Valor por hora</span><br>
                                            <span class="text-success">
                                                R$ {{ number_format($quadra->hourly_rate, 2, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-3">
                                        @if ($quadra->active)
                                            <button type="button" class="btn btn-warning btn-sm flex-fill"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalDesativarQuadraSys{{ $quadra->id }}">
                                                Desativar
                                            </button>
                                        @else
                                            <form method="POST"
                                                  action="{{ route('admin.arenas.courts.activate', [$quadra->arena, $quadra]) }}"
                                                  class="flex-fill">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-success btn-sm w-100">Ativar</button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn btn-danger btn-sm flex-fill"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalExcluirQuadraSys{{ $quadra->id }}">
                                            Excluir
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary btn-sm w-100 court-sys-toggle"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#detalhesQuadraSys{{ $quadra->id }}"
                                    aria-controls="detalhesQuadraSys{{ $quadra->id }}"
                                    aria-expanded="false">
                                Ver detalhes
                            </button>
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

@foreach ($quadras as $quadra)
    @if ($quadra->arena)
        <div class="modal fade" id="modalDesativarQuadraSys{{ $quadra->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.arenas.courts.deactivate', [$quadra->arena, $quadra]) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h5 class="modal-title">Desativar quadra</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            Deseja realmente desativar <strong>{{ $quadra->name }}</strong>?
                            As reservas pendentes ou confirmadas desta quadra serão canceladas.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning">Sim, desativar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalExcluirQuadraSys{{ $quadra->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.arenas.courts.destroy', [$quadra->arena, $quadra]) }}">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title text-danger">Excluir quadra</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                Deseja realmente excluir <strong>{{ $quadra->name }}</strong>?
                            </div>
                            As reservas ativas serão canceladas e o histórico será preservado.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Sim, excluir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

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

        // Ver / Fechar detalhes da quadra (abre um por vez e troca o texto do botão).
        let quadraAberta = null;
        document.querySelectorAll('.court-system-card .collapse').forEach(function (details) {
            const button = document.querySelector(`.court-sys-toggle[data-bs-target="#${details.id}"]`);
            if (!button) return;

            details.addEventListener('shown.bs.collapse', function () {
                if (quadraAberta && quadraAberta !== details) {
                    bootstrap.Collapse.getOrCreateInstance(quadraAberta, { toggle: false }).hide();
                }
                quadraAberta = details;
                button.textContent = 'Fechar detalhes';
            });

            details.addEventListener('hidden.bs.collapse', function () {
                button.textContent = 'Ver detalhes';
                if (quadraAberta === details) {
                    quadraAberta = null;
                }
            });
        });
    });
</script>
@endsection

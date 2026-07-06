@extends('layouts.main')

@section('title', 'Quadras do Sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm mb-3">← Voltar ao painel</a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Quadras do sistema</h1>
        <p class="dashboard-subtitle mb-0">Todas as quadras, arenas e empresas.</p>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-md-6">
            <div class="input-group arena-search-box shadow-sm">
                <input type="search" class="form-control"
                       placeholder="Nome da quadra, arena, empresa ou proprietário"
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
                <div class="dashboard-box p-3 d-flex flex-column court-system-card">
                    <span class="badge bg-danger text-white align-self-start mb-2 px-2 py-0 fw-normal">Quadra</span>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1">{{ $quadra->name }}</h2>
                                <span class="badge fw-normal {{ $quadra->active ? 'bg-success' : 'bg-warning text-dark' }}"
                                      data-court-status>
                                    {{ $quadra->active ? 'Ativa' : 'Desativada' }}
                                </span>
                            </div>
                            <button type="button"
                                    class="btn btn-sm border-0 p-0 fs-4 lh-1 court-corner-toggle"
                                    data-court-details-toggle="courtDetails-{{ $quadra->getKey() }}"
                                    aria-controls="courtDetails-{{ $quadra->getKey() }}"
                                    aria-expanded="false"
                                    aria-label="Mostrar detalhes">
                                <span aria-hidden="true">⌃</span>
                            </button>
                        </div>

                        <div class="row g-3">
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

                    <div class="collapse mb-3" id="courtDetails-{{ $quadra->getKey() }}">
                        <div class="border-top pt-3">
                            <div class="row g-3">
                                <div class="col-12">
                                    <span class="small text-dark fw-bold">Descrição</span><br>
                                    <span>{{ $quadra->description ?: 'Sem descrição' }}</span>
                                </div>
                                <div class="col-12">
                                    <span class="small text-dark fw-bold">Proprietário</span><br>
                                    <span>{{ $quadra->arena?->owner?->user?->name ?? '—' }}</span>
                                </div>
                                <div class="col-12">
                                    <span class="small text-dark fw-bold">Esportes</span><br>
                                    <span>{{ $quadra->sports->pluck('name')->join(', ') ?: 'Não informados' }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="small text-dark fw-bold">Valor por hora</span><br>
                                    <span>R$ {{ number_format($quadra->hourly_rate, 2, ',', '.') }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="small text-dark fw-bold">Situação</span><br>
                                    <span class="badge fw-normal {{ $quadra->active ? 'bg-success' : 'bg-warning text-dark' }}"
                                          data-court-status>
                                        {{ $quadra->active ? 'Ativa' : 'Desativada' }}
                                    </span>
                                </div>
                                <div class="col-6">
                                    <span class="small text-dark fw-bold">Cadastro</span><br>
                                    <span>{{ optional($quadra->created_at)->format('d/m/Y') ?? '—' }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="small text-dark fw-bold">Última atualização</span><br>
                                    <span>{{ optional($quadra->updated_at)->format('d/m/Y H:i') ?? '—' }}</span>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                @if ($quadra->arena)
                                    @if ($quadra->active)
                                        <form method="POST"
                                              action="{{ route('admin.arenas.courts.deactivate', [$quadra->arena, $quadra]) }}"
                                              class="flex-fill"
                                              data-court-toggle-form
                                              data-active="1"
                                              data-activate-url="{{ route('admin.arenas.courts.activate', [$quadra->arena, $quadra]) }}"
                                              data-deactivate-url="{{ route('admin.arenas.courts.deactivate', [$quadra->arena, $quadra]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-outline-warning btn-sm w-100">Desativar</button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.arenas.courts.activate', [$quadra->arena, $quadra]) }}"
                                              class="flex-fill"
                                              data-court-toggle-form
                                              data-active="0"
                                              data-activate-url="{{ route('admin.arenas.courts.activate', [$quadra->arena, $quadra]) }}"
                                              data-deactivate-url="{{ route('admin.arenas.courts.deactivate', [$quadra->arena, $quadra]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-outline-success btn-sm w-100">Ativar</button>
                                        </form>
                                    @endif

                                    <form method="POST"
                                          action="{{ route('admin.arenas.courts.destroy', [$quadra->arena, $quadra]) }}"
                                          class="flex-fill"
                                          data-court-delete-form>
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm w-100">Excluir</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm w-100 mt-auto"
                            data-court-details-toggle="courtDetails-{{ $quadra->getKey() }}"
                            aria-controls="courtDetails-{{ $quadra->getKey() }}"
                            aria-expanded="false">
                        Ver detalhes
                    </button>
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

<div class="court-details-backdrop" data-court-details-backdrop aria-hidden="true"></div>

<div class="modal fade" id="courtActionConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" data-court-confirm-title>Confirmar ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" data-court-confirm-message></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" data-court-confirm-action>Confirmar</button>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed start-50 translate-middle-x px-3 w-100"
     style="top: 82px; z-index: 1060; max-width: 680px;"
     data-court-feedback></div>

<style>
    .court-corner-toggle {
        color: #021b35;
        min-width: 1.5rem;
    }

    .court-card-shell.is-open {
        position: relative;
        z-index: 1015;
    }

    .court-card-shell.is-open .court-system-card {
        box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, .35);
    }

    .court-details-backdrop {
        display: none;
        position: fixed;
        z-index: 1010;
        inset: 0;
        background: rgba(2, 27, 53, .68);
    }

    .court-details-backdrop.is-visible {
        display: block;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const backdrop = document.querySelector('[data-court-details-backdrop]');
        const toggles = document.querySelectorAll('[data-court-details-toggle]');
        const feedback = document.querySelector('[data-court-feedback]');
        const searchInput = document.querySelector('[data-court-search-input]');
        const courtCards = document.querySelectorAll('[data-court-card]');
        const noResults = document.querySelector('[data-court-no-results]');
        const confirmModalElement = document.getElementById('courtActionConfirmModal');
        const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalElement);
        const confirmTitle = confirmModalElement.querySelector('[data-court-confirm-title]');
        const confirmMessage = confirmModalElement.querySelector('[data-court-confirm-message]');
        const confirmButton = confirmModalElement.querySelector('[data-court-confirm-action]');
        let activeDetails = null;
        let activeShell = null;

        function normalizeSearch(value) {
            return (value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, '')
                .toLowerCase();
        }

        function askConfirmation(options) {
            return new Promise(function (resolve) {
                let answered = false;

                confirmTitle.textContent = options.title;
                confirmMessage.innerHTML = options.message;
                confirmButton.textContent = options.buttonText;
                confirmButton.className = `btn ${options.buttonClass}`;

                function confirm() {
                    answered = true;
                    confirmButton.removeEventListener('click', confirm);
                    confirmModal.hide();
                    resolve(true);
                }

                confirmButton.addEventListener('click', confirm);
                confirmModalElement.addEventListener('hidden.bs.modal', function cancelled() {
                    confirmModalElement.removeEventListener('hidden.bs.modal', cancelled);
                    confirmButton.removeEventListener('click', confirm);

                    if (!answered) {
                        resolve(false);
                    }
                }, { once: true });

                confirmModal.show();
            });
        }

        function showFeedback(message, type = 'success') {
            feedback.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show shadow" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            `;

            window.setTimeout(function () {
                feedback.innerHTML = '';
            }, 4500);
        }

        async function sendForm(form) {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });

            if (!response.ok) {
                let message = 'Não foi possível concluir a operação.';

                try {
                    const data = await response.json();
                    message = data.message || message;
                } catch (error) {
                    // Mantém a mensagem padrão quando a resposta não for JSON.
                }

                throw new Error(message);
            }
        }

        function updateButtons(detailsId, opened) {
            document.querySelectorAll(`[data-court-details-toggle="${detailsId}"]`).forEach(function (button) {
                button.setAttribute('aria-expanded', opened ? 'true' : 'false');

                if (button.classList.contains('court-corner-toggle')) {
                    button.querySelector('span').textContent = opened ? '⌄' : '⌃';
                    button.setAttribute('aria-label', opened ? 'Fechar detalhes' : 'Mostrar detalhes');
                } else {
                    button.textContent = opened ? 'Fechar detalhes' : 'Ver detalhes';
                }
            });
        }

        function clearActive(details, shell) {
            shell?.classList.remove('is-open');

            if (details) {
                updateButtons(details.id, false);
            }

            backdrop.classList.remove('is-visible');
            backdrop.setAttribute('aria-hidden', 'true');
        }

        function closeActive() {
            if (activeDetails) {
                bootstrap.Collapse.getOrCreateInstance(activeDetails, { toggle: false }).hide();
            }
        }

        function openDetails(details, shell) {
            if (activeDetails && activeDetails !== details) {
                const previousDetails = activeDetails;
                const previousShell = activeShell;

                activeDetails = null;
                activeShell = null;
                bootstrap.Collapse.getOrCreateInstance(previousDetails, { toggle: false }).hide();
                clearActive(previousDetails, previousShell);
            }

            activeDetails = details;
            activeShell = shell;
            shell.classList.add('is-open');
            backdrop.classList.add('is-visible');
            backdrop.setAttribute('aria-hidden', 'false');
            updateButtons(details.id, true);
            bootstrap.Collapse.getOrCreateInstance(details, { toggle: false }).show();
        }

        toggles.forEach(function (button) {
            button.addEventListener('click', function () {
                const details = document.getElementById(button.dataset.courtDetailsToggle);

                if (!details) {
                    return;
                }

                const shell = button.closest('.court-card-shell');

                if (activeDetails === details) {
                    closeActive();
                } else {
                    openDetails(details, shell);
                }
            });
        });

        document.querySelectorAll('.court-card-shell .collapse').forEach(function (details) {
            details.addEventListener('hidden.bs.collapse', function () {
                const shell = details.closest('.court-card-shell');

                if (activeDetails === details) {
                    clearActive(details, shell);
                    activeDetails = null;
                    activeShell = null;
                } else {
                    shell.classList.remove('is-open');
                    updateButtons(details.id, false);
                }
            });
        });

        document.querySelectorAll('[data-court-toggle-form]').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const currentlyActive = form.dataset.active === '1';
                const confirmed = await askConfirmation(currentlyActive ? {
                    title: 'Desativar quadra',
                    message: `
                        <p>Tem certeza que deseja desativar esta quadra?</p>
                        <div class="alert alert-warning mb-0">
                            A quadra deixará de aparecer para os clientes e as reservas pendentes ou confirmadas
                            vinculadas a ela serão canceladas.
                        </div>
                    `,
                    buttonText: 'Sim, desativar',
                    buttonClass: 'btn-warning',
                } : {
                    title: 'Ativar quadra',
                    message: `
                        <p>Tem certeza que deseja ativar esta quadra?</p>
                        <div class="alert alert-success mb-0">
                            A quadra voltará a ficar disponível para os clientes, desde que a arena esteja ativa.
                        </div>
                    `,
                    buttonText: 'Sim, ativar',
                    buttonClass: 'btn-success',
                });

                if (!confirmed) {
                    return;
                }

                const button = form.querySelector('button');
                button.disabled = true;

                try {
                    await sendForm(form);

                    const nowActive = !currentlyActive;
                    const shell = form.closest('.court-card-shell');

                    shell.querySelectorAll('[data-court-status]').forEach(function (status) {
                        status.textContent = nowActive ? 'Ativa' : 'Desativada';
                        status.className = `badge fw-normal ${nowActive ? 'bg-success' : 'bg-warning text-dark'}`;
                        status.setAttribute('data-court-status', '');
                    });

                    form.dataset.active = nowActive ? '1' : '0';
                    form.action = nowActive ? form.dataset.deactivateUrl : form.dataset.activateUrl;
                    button.textContent = nowActive ? 'Desativar' : 'Ativar';
                    button.className = `btn btn-sm w-100 ${nowActive ? 'btn-outline-warning' : 'btn-outline-success'}`;
                    showFeedback(`Quadra ${nowActive ? 'ativada' : 'desativada'} com sucesso.`);
                } catch (error) {
                    showFeedback(error.message, 'danger');
                } finally {
                    button.disabled = false;
                }
            });
        });

        document.querySelectorAll('[data-court-delete-form]').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const confirmed = await askConfirmation({
                    title: 'Excluir quadra',
                    message: `
                        <p>Tem certeza que deseja excluir esta quadra?</p>
                        <div class="alert alert-danger mb-0">
                            A quadra será removida das áreas da arena e dos clientes. As reservas pendentes ou
                            confirmadas serão canceladas, mas o histórico permanecerá preservado.
                        </div>
                    `,
                    buttonText: 'Sim, excluir',
                    buttonClass: 'btn-danger',
                });

                if (!confirmed) {
                    return;
                }

                const button = form.querySelector('button');
                button.disabled = true;

                try {
                    await sendForm(form);

                    const shell = form.closest('.court-card-shell');
                    const details = shell.querySelector('.collapse');

                    if (activeShell === shell) {
                        clearActive(details, shell);
                        activeDetails = null;
                        activeShell = null;
                    }

                    shell.remove();
                    showFeedback('Quadra excluída com sucesso.');
                } catch (error) {
                    button.disabled = false;
                    showFeedback(error.message, 'danger');
                }
            });
        });

        searchInput.addEventListener('input', function () {
            const term = normalizeSearch(searchInput.value);
            let visibleCards = 0;

            closeActive();

            courtCards.forEach(function (card) {
                const matches = normalizeSearch(card.dataset.courtSearch).includes(term);
                card.classList.toggle('d-none', !matches);

                if (matches) {
                    visibleCards++;
                }
            });

            noResults.classList.toggle('d-none', visibleCards > 0);
        });

        backdrop.addEventListener('click', closeActive);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeActive();
            }
        });
    });
</script>
@endsection

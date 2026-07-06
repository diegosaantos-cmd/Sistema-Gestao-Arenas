@extends('layouts.main')

@section('title', 'Arenas do Sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm mb-3">← Voltar ao painel</a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Arenas do sistema</h1>
        <p class="dashboard-subtitle mb-0">Todas as arenas cadastradas e suas empresas.</p>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-md-6">
            <div class="input-group arena-search-box shadow-sm">
                <input type="search" class="form-control"
                       placeholder="Nome da arena, empresa ou proprietário"
                       aria-label="Pesquisar arena"
                       data-arena-search-input>
                <span class="input-group-text bg-white border-0">
                    <i class="bi bi-search text-secondary"></i>
                </span>
            </div>
        </div>
    </div>

    @php
        $diasSemana = [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
        ];
    @endphp

    <div class="row g-4">
        @forelse ($arenas as $arena)
            <div class="col-6 col-lg-3 arena-card-shell"
                 data-arena-card
                 data-arena-search="{{ $arena->name }} {{ $arena->owner?->company_name }} {{ $arena->owner?->user?->name }}">
                <div class="dashboard-box p-3 d-flex flex-column arena-system-card">
                    <span class="badge bg-danger text-white align-self-start mb-2 px-2 py-0 fw-normal">Arena</span>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1">{{ $arena->name }}</h2>
                                <span class="badge fw-normal {{ $arena->active ? 'bg-success' : 'bg-warning text-dark' }}"
                                      data-arena-status>
                                    {{ $arena->active ? 'Ativa' : 'Desativada' }}
                                </span>
                            </div>
                            <button type="button"
                                    class="btn btn-sm border-0 p-0 fs-4 lh-1 arena-corner-toggle"
                                    data-arena-details-toggle="arenaDetalhes-{{ $arena->getKey() }}"
                                    aria-controls="arenaDetalhes-{{ $arena->getKey() }}"
                                    aria-expanded="false"
                                    aria-label="Mostrar detalhes">
                                <span aria-hidden="true">⌃</span>
                            </button>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <span class="small text-dark fw-bold">Empresa</span><br>
                                <span>{{ $arena->owner?->company_name ?? '—' }}</span>
                            </div>
                            <div class="col-6">
                                <span class="small text-dark fw-bold">Proprietário</span><br>
                                <span>{{ $arena->owner?->user?->name ?? '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="collapse mb-3" id="arenaDetalhes-{{ $arena->getKey() }}">
                        <div class="border-top pt-3">
                            <div class="row g-3">
                                <div class="col-12">
                                    <span class="small text-dark fw-bold">Descrição</span><br>
                                    <span>{{ $arena->description ?: 'Sem descrição' }}</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="small text-dark fw-bold">E-mail</span><br>
                                    <span class="text-break">{{ $arena->contact_email ?: '—' }}</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="small text-dark fw-bold">Telefone</span><br>
                                    <span class="text-break">{{ $arena->phone ?: '—' }}</span>
                                </div>
                                <div class="col-12">
                                    <span class="small text-dark fw-bold">Endereço</span><br>
                                    <span>
                                        {{ $arena->address_rua }}, {{ $arena->address_numero }}
                                        — {{ $arena->address_bairro }}
                                    </span>
                                </div>
                                <div class="col-6">
                                    <span class="small text-dark fw-bold">Quadras</span><br>
                                    <span>{{ $arena->courts_count }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="small text-dark fw-bold">Funcionários</span><br>
                                    <span>{{ $arena->employees_count }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="small text-dark fw-bold">Cadastro</span><br>
                                    <span>{{ optional($arena->created_at)->format('d/m/Y') ?? '—' }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="small text-dark fw-bold">Situação</span><br>
                                    <span class="badge fw-normal {{ $arena->active ? 'bg-success' : 'bg-warning text-dark' }}"
                                          data-arena-status>
                                        {{ $arena->active ? 'Ativa' : 'Desativada' }}
                                    </span>
                                </div>
                                <div class="col-12">
                                    <span class="small text-dark fw-bold">Formas de pagamento</span><br>
                                    <span>{{ $arena->paymentMethods->pluck('label')->join(', ') ?: 'Não informadas' }}</span>
                                </div>
                                <div class="col-12">
                                    <span class="small text-dark fw-bold">Dias e horários de funcionamento</span>
                                    <div class="row g-2 mt-1">
                                        @forelse ($arena->businessHours as $horario)
                                            <div class="col-12 col-sm-6">
                                                <strong class="d-inline d-sm-block">
                                                    {{ $diasSemana[$horario->day_of_week] ?? 'Dia' }}<span class="d-sm-none">:</span>
                                                </strong>
                                                <span>{{ substr($horario->opens_at, 0, 5) }}–{{ substr($horario->closes_at, 0, 5) }}</span>
                                            </div>
                                        @empty
                                            <div class="col-12 text-muted">Horários não informados.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-12">
                                    <span class="small text-dark fw-bold">Política de Privacidade e Termos</span><br>
                                    @if ($arena->owner?->user?->terms_accepted_at)
                                        <span class="badge bg-success fw-normal">Aceitos</span>
                                        <span class="small text-muted ms-1">
                                            {{ $arena->owner->user->terms_accepted_at->format('d/m/Y H:i') }}
                                        </span>
                                    @else
                                        <span class="text-muted">Aceite não registrado</span>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <a href="{{ route('admin.arenas.show', [$arena, 'origem' => 'arenas_sistema']) }}"
                                   class="btn btn-outline-primary btn-sm flex-fill">
                                    Ver arena
                                </a>

                                @if ($arena->active)
                                    <form method="POST" action="{{ route('admin.arenas.deactivate', $arena) }}"
                                          class="flex-fill"
                                          data-arena-toggle-form
                                          data-active="1"
                                          data-activate-url="{{ route('admin.arenas.activate', $arena) }}"
                                          data-deactivate-url="{{ route('admin.arenas.deactivate', $arena) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-outline-warning btn-sm w-100">Desativar</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.arenas.activate', $arena) }}"
                                          class="flex-fill"
                                          data-arena-toggle-form
                                          data-active="0"
                                          data-activate-url="{{ route('admin.arenas.activate', $arena) }}"
                                          data-deactivate-url="{{ route('admin.arenas.deactivate', $arena) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-outline-success btn-sm w-100">Ativar</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.arenas.destroy', $arena) }}"
                                      class="flex-fill"
                                      data-arena-delete-form>
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm w-100">Excluir</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm w-100 mt-auto"
                            data-arena-details-toggle="arenaDetalhes-{{ $arena->getKey() }}"
                            aria-controls="arenaDetalhes-{{ $arena->getKey() }}"
                            aria-expanded="false">
                        Ver detalhes
                    </button>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="dashboard-box text-center text-muted">Nenhuma arena cadastrada.</div>
            </div>
        @endforelse

        <div class="col-12 d-none" data-arena-no-results>
            <div class="dashboard-box text-center text-muted">
                Nenhuma arena encontrada.
            </div>
        </div>
    </div>
</div>

<div class="arena-details-backdrop" data-arena-details-backdrop aria-hidden="true"></div>

<div class="modal fade" id="arenaActionConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" data-confirm-title>Confirmar ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" data-confirm-message></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" data-confirm-action>Confirmar</button>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed start-50 translate-middle-x px-3 w-100"
     style="top: 82px; z-index: 1060; max-width: 680px;"
     data-arena-feedback></div>

<style>
    .arena-corner-toggle {
        color: #021b35;
        min-width: 1.5rem;
    }

    .arena-card-shell.is-open {
        position: relative;
        z-index: 1015;
    }

    .arena-card-shell.is-open .arena-system-card {
        box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, .35);
    }

    .arena-details-backdrop {
        display: none;
        position: fixed;
        z-index: 1010;
        inset: 0;
        background: rgba(2, 27, 53, .68);
    }

    .arena-details-backdrop.is-visible {
        display: block;
    }

</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const backdrop = document.querySelector('[data-arena-details-backdrop]');
        const toggles = document.querySelectorAll('[data-arena-details-toggle]');
        const feedback = document.querySelector('[data-arena-feedback]');
        const confirmModalElement = document.getElementById('arenaActionConfirmModal');
        const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalElement);
        const confirmTitle = confirmModalElement.querySelector('[data-confirm-title]');
        const confirmMessage = confirmModalElement.querySelector('[data-confirm-message]');
        const confirmButton = confirmModalElement.querySelector('[data-confirm-action]');
        const searchInput = document.querySelector('[data-arena-search-input]');
        const arenaCards = document.querySelectorAll('[data-arena-card]');
        const noResults = document.querySelector('[data-arena-no-results]');
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
                throw new Error('Não foi possível concluir a operação.');
            }
        }

        function updateButtons(detailsId, opened) {
            document.querySelectorAll(`[data-arena-details-toggle="${detailsId}"]`).forEach(function (button) {
                button.setAttribute('aria-expanded', opened ? 'true' : 'false');

                if (button.classList.contains('arena-corner-toggle')) {
                    button.querySelector('span').textContent = opened ? '⌄' : '⌃';
                    button.setAttribute('aria-label', opened ? 'Fechar detalhes' : 'Mostrar detalhes');
                } else {
                    button.textContent = opened ? 'Fechar detalhes' : 'Ver detalhes';
                }
            });
        }

        function clearActive(details, shell) {
            if (shell) {
                shell.classList.remove('is-open');
            }

            if (details) {
                updateButtons(details.id, false);
            }

            backdrop.classList.remove('is-visible');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('arena-details-open');
        }

        function closeActive() {
            if (!activeDetails) {
                return;
            }

            bootstrap.Collapse.getOrCreateInstance(activeDetails, { toggle: false }).hide();
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
            document.body.classList.add('arena-details-open');
            updateButtons(details.id, true);
            bootstrap.Collapse.getOrCreateInstance(details, { toggle: false }).show();
        }

        toggles.forEach(function (button) {
            button.addEventListener('click', function () {
                const details = document.getElementById(button.dataset.arenaDetailsToggle);

                if (!details) {
                    return;
                }

                const shell = button.closest('.arena-card-shell');

                if (activeDetails === details) {
                    closeActive();
                } else {
                    openDetails(details, shell);
                }
            });
        });

        document.querySelectorAll('.arena-card-shell .collapse').forEach(function (details) {
            details.addEventListener('hidden.bs.collapse', function () {
                const shell = details.closest('.arena-card-shell');

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

        document.querySelectorAll('[data-arena-toggle-form]').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const currentlyActive = form.dataset.active === '1';
                const confirmed = await askConfirmation(currentlyActive ? {
                    title: 'Desativar arena',
                    message: `
                        <p>Tem certeza que deseja desativar esta arena?</p>
                        <div class="alert alert-warning mb-0">
                            A arena deixará de aparecer para os clientes, todas as quadras serão desativadas,
                            as reservas pendentes ou confirmadas serão canceladas e o proprietário não poderá
                            reativá-la enquanto o bloqueio do administrador estiver ativo.
                        </div>
                    `,
                    buttonText: 'Sim, desativar',
                    buttonClass: 'btn-warning',
                } : {
                    title: 'Ativar arena',
                    message: `
                        <p>Tem certeza que deseja ativar esta arena?</p>
                        <div class="alert alert-success mb-0">
                            A arena e suas quadras serão reativadas e voltarão a ficar disponíveis para os clientes.
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
                    const shell = form.closest('.arena-card-shell');

                    shell.querySelectorAll('[data-arena-status]').forEach(function (status) {
                        status.textContent = nowActive ? 'Ativa' : 'Desativada';
                        status.className = `badge fw-normal ${nowActive ? 'bg-success' : 'bg-warning text-dark'}`;
                        status.setAttribute('data-arena-status', '');
                    });

                    form.dataset.active = nowActive ? '1' : '0';
                    form.action = nowActive ? form.dataset.deactivateUrl : form.dataset.activateUrl;
                    button.textContent = nowActive ? 'Desativar' : 'Ativar';
                    button.className = `btn btn-sm w-100 ${nowActive ? 'btn-outline-warning' : 'btn-outline-success'}`;

                    showFeedback(`Arena ${nowActive ? 'ativada' : 'desativada'} com sucesso.`);
                } catch (error) {
                    showFeedback(error.message, 'danger');
                } finally {
                    button.disabled = false;
                }
            });
        });

        document.querySelectorAll('[data-arena-delete-form]').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const confirmed = await askConfirmation({
                    title: 'Excluir arena',
                    message: `
                        <p>Tem certeza que deseja excluir esta arena?</p>
                        <div class="alert alert-danger mb-0">
                            A arena será removida das áreas do proprietário e dos clientes, suas quadras serão
                            desativadas e as reservas pendentes ou confirmadas serão canceladas.
                            O histórico de reservas permanecerá preservado no sistema.
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

                    const shell = form.closest('.arena-card-shell');
                    const details = shell.querySelector('.collapse');

                    if (activeShell === shell) {
                        clearActive(details, shell);
                        activeDetails = null;
                        activeShell = null;
                    }

                    shell.remove();
                    showFeedback('Arena excluída com sucesso.');
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

            arenaCards.forEach(function (card) {
                const matches = normalizeSearch(card.dataset.arenaSearch).includes(term);
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

@extends('layouts.main')

@section('title', 'Empresas e Proprietários')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <x-back :href="route('admin.dashboard')" />

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Empresas / Proprietários</h1>
        <p class="dashboard-subtitle mb-0">Consulte e administre todas as empresas cadastradas.</p>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-md-6">
            <div class="input-group arena-search-box shadow-sm">
                <input type="search" class="form-control"
                       placeholder="Pesquise por empresa ou proprietário"
                       aria-label="Pesquisar empresa"
                       data-owner-search-input>
                <span class="input-group-text bg-white border-0">
                    <i class="bi bi-search text-secondary"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @forelse ($proprietarios as $proprietario)
            <div class="col-12 col-sm-6 col-lg-3 owner-card-shell"
                 data-owner-card
                 data-owner-search="{{ $proprietario->company_name }} {{ $proprietario->user?->name }} {{ $proprietario->tax_id }} {{ $proprietario->user?->email }} {{ $proprietario->user?->phone }}">
                <div class="dashboard-box p-3 d-flex flex-column h-100 owner-system-card">
                    <span class="badge bg-danger text-white align-self-start mb-2 px-2 py-0 fw-normal">Empresa</span>

                    <div class="mb-3">
                        <h2 class="h5 fw-bold mb-1">{{ $proprietario->company_name }}</h2>
                        <span class="badge fw-normal {{ $proprietario->active ? 'bg-success' : 'bg-warning text-dark' }}"
                              data-owner-status>
                            {{ $proprietario->active ? 'Ativa' : 'Desativada' }}
                        </span>

                        <div class="mt-2">
                            <span class="small text-dark fw-bold">Proprietário</span><br>
                            <span>{{ $proprietario->user?->name ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="mt-auto d-grid gap-2">
                        <a href="{{ route('admin.owners.show', $proprietario) }}"
                           class="btn btn-primary btn-sm">
                            Ver detalhes
                        </a>

                        <div class="d-flex flex-wrap gap-2">
                            @if ($proprietario->active)
                                <form method="POST" action="{{ route('admin.owners.deactivate', $proprietario) }}"
                                      class="flex-fill"
                                      data-owner-toggle-form
                                      data-active="1"
                                      data-activate-url="{{ route('admin.owners.activate', $proprietario) }}"
                                      data-deactivate-url="{{ route('admin.owners.deactivate', $proprietario) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-warning btn-sm w-100">Desativar</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.owners.activate', $proprietario) }}"
                                      class="flex-fill"
                                      data-owner-toggle-form
                                      data-active="0"
                                      data-activate-url="{{ route('admin.owners.activate', $proprietario) }}"
                                      data-deactivate-url="{{ route('admin.owners.deactivate', $proprietario) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-success btn-sm w-100">Ativar</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.owners.destroy', $proprietario) }}"
                                  class="flex-fill"
                                  data-owner-delete-form>
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm w-100">Excluir</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="dashboard-box text-center text-muted">Nenhuma empresa cadastrada.</div>
            </div>
        @endforelse

        <div class="col-12 d-none" data-owner-no-results>
            <div class="dashboard-box text-center text-muted">Nenhuma empresa encontrada.</div>
        </div>
    </div>
</div>

<div class="modal fade" id="ownerActionConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" data-owner-confirm-title>Confirmar ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" data-owner-confirm-message></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" data-owner-confirm-action>Confirmar</button>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed start-50 translate-middle-x px-3 w-100"
     style="top: 82px; z-index: 1060; max-width: 680px;"
     data-owner-feedback></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const feedback = document.querySelector('[data-owner-feedback]');
        const searchInput = document.querySelector('[data-owner-search-input]');
        const ownerCards = document.querySelectorAll('[data-owner-card]');
        const noResults = document.querySelector('[data-owner-no-results]');
        const confirmModalElement = document.getElementById('ownerActionConfirmModal');
        const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalElement);
        const confirmTitle = confirmModalElement.querySelector('[data-owner-confirm-title]');
        const confirmMessage = confirmModalElement.querySelector('[data-owner-confirm-message]');
        const confirmButton = confirmModalElement.querySelector('[data-owner-confirm-action]');

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

        document.querySelectorAll('[data-owner-toggle-form]').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const currentlyActive = form.dataset.active === '1';
                const confirmed = await askConfirmation(currentlyActive ? {
                    title: 'Desativar empresa',
                    message: `
                        <p>Tem certeza que deseja desativar esta empresa?</p>
                        <div class="alert alert-warning mb-0">
                            O proprietário perderá o acesso, todas as arenas e quadras serão desativadas
                            e a empresa não poderá se reativar sem autorização do administrador.
                        </div>
                    `,
                    buttonText: 'Sim, desativar',
                    buttonClass: 'btn-warning',
                } : {
                    title: 'Ativar empresa',
                    message: `
                        <p>Tem certeza que deseja ativar esta empresa?</p>
                        <div class="alert alert-success mb-0">
                            O acesso do proprietário, as arenas e as quadras da empresa serão reativados.
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
                    const shell = form.closest('.owner-card-shell');
                    const status = shell.querySelector('[data-owner-status]');

                    status.textContent = nowActive ? 'Ativa' : 'Desativada';
                    status.className = `badge fw-normal ${nowActive ? 'bg-success' : 'bg-warning text-dark'}`;
                    status.setAttribute('data-owner-status', '');
                    form.dataset.active = nowActive ? '1' : '0';
                    form.action = nowActive ? form.dataset.deactivateUrl : form.dataset.activateUrl;
                    button.textContent = nowActive ? 'Desativar' : 'Ativar';
                    button.className = `btn btn-sm w-100 ${nowActive ? 'btn-warning' : 'btn-success'}`;
                    showFeedback(`Empresa ${nowActive ? 'ativada' : 'desativada'} com sucesso.`);
                } catch (error) {
                    showFeedback(error.message, 'danger');
                } finally {
                    button.disabled = false;
                }
            });
        });

        document.querySelectorAll('[data-owner-delete-form]').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const confirmed = await askConfirmation({
                    title: 'Excluir empresa',
                    message: `
                        <p>Tem certeza que deseja excluir esta empresa?</p>
                        <div class="alert alert-danger mb-0">
                            O proprietário perderá o acesso, as arenas e quadras serão desativadas e a empresa
                            será removida das áreas públicas. Os históricos permanecerão preservados.
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

                    form.closest('.owner-card-shell').remove();
                    showFeedback('Empresa excluída com sucesso.');
                } catch (error) {
                    button.disabled = false;
                    showFeedback(error.message, 'danger');
                }
            });
        });

        searchInput.addEventListener('input', function () {
            const term = normalizeSearch(searchInput.value);
            let visibleCards = 0;

            ownerCards.forEach(function (card) {
                const matches = normalizeSearch(card.dataset.ownerSearch).includes(term);
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

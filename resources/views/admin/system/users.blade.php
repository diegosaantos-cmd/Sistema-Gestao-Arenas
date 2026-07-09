@extends('layouts.main')

@section('title', 'Clientes do Sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm mb-3">← Voltar ao painel</a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Clientes cadastrados no sistema</h1>
        <p class="dashboard-subtitle mb-0">Consulte e gerencie os clientes cadastrados.</p>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-md-6">
            <form data-client-search
                  data-endpoint="{{ route('admin.system.clients.data') }}"
                  data-target="systemClientsBody">
                <div class="input-group arena-search-box shadow-sm">
                    <input type="search"
                           name="busca_cliente"
                           value="{{ request('busca_cliente') }}"
                           class="form-control"
                           placeholder="Pesquise por nome ou e-mail"
                           aria-label="Pesquisar cliente">
                    <span class="input-group-text bg-white border-0">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard-box p-0 overflow-hidden system-clients-box">
        <div class="table-responsive system-clients-container" style="max-height: 72vh; overflow-y: auto;">
            <table class="table table-sm table-hover align-middle mb-0 small system-clients-table admin-sticky-table"
                   style="table-layout: fixed; width: 100%;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3" style="width: 14%;">Cliente</th>
                        <th style="width: 19%;">E-mail</th>
                        <th class="client-secondary-column" style="width: 13%;">Telefone</th>
                        <th class="client-secondary-column" style="width: 11%;">Nascimento</th>
                        <th class="client-secondary-column" style="width: 13%;">Termos</th>
                        <th class="client-secondary-column" style="width: 11%;">Cadastro</th>
                        <th style="width: 8%;">Situação</th>
                        <th class="text-end pe-3" style="width: 11%;">Ações</th>
                    </tr>
                </thead>
                <tbody id="systemClientsBody">
                    @include('admin.system._client-rows', ['usuarios' => $usuarios])
                </tbody>
            </table>

            <div class="text-center py-3 {{ $usuarios->nextPageUrl() ? '' : 'd-none' }}"
                 data-infinite-clients
                 data-target="systemClientsBody"
                 data-next-url="{{ $usuarios->nextPageUrl() }}"
                 data-mobile-viewport="true"
                 data-version="0">
                <div class="spinner-border spinner-border-sm text-primary d-none"
                     role="status"
                     data-client-spinner>
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media (max-width: 767.98px) {
        .system-clients-container {
            max-height: none !important;
            overflow: visible !important;
        }

        .system-clients-box {
            overflow: visible !important;
        }

        .system-clients-table thead th {
            top: 70px;
        }

        .system-clients-table {
            table-layout: fixed !important;
            font-size: .72rem;
        }

        .system-clients-table .client-secondary-column {
            display: none;
        }

        .system-clients-table th,
        .system-clients-table td {
            padding: .55rem .25rem !important;
            vertical-align: middle;
        }

        .system-clients-table th:nth-child(1),
        .system-clients-table td:nth-child(1) {
            width: 24% !important;
        }

        .system-clients-table th:nth-child(2),
        .system-clients-table td:nth-child(2) {
            width: 40% !important;
        }

        .system-clients-table th:nth-child(7),
        .system-clients-table td:nth-child(7) {
            width: 17% !important;
        }

        .system-clients-table th:nth-child(8),
        .system-clients-table td:nth-child(8) {
            width: 19% !important;
        }

        .system-clients-table .client-main-email {
            overflow-wrap: anywhere;
        }

        .system-clients-table .client-main-actions .btn {
            font-size: .68rem;
            padding: .3rem .2rem;
        }

        .system-clients-table .client-mobile-details-row td {
            padding: .85rem !important;
        }

        .system-clients-table .client-mobile-details-row .client-system-actions {
            width: 100%;
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

@include('admin.clients._infinite-script')

{{-- Modal de confirmação compartilhado (substitui o confirm() do navegador).
     As ações Bloquear / Desbloquear / Excluir abrem este modal via data-attributes;
     ao confirmar, o formulário correspondente é enviado. Funciona também para as
     linhas carregadas por scroll infinito, pois o clique é tratado por delegação. --}}
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" data-confirm-modal-title>Confirmar ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" data-confirm-modal-message>Tem certeza?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" data-confirm-modal-ok>Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var modalEl = document.getElementById('confirmActionModal');
        if (!modalEl) return;

        var titleEl = modalEl.querySelector('[data-confirm-modal-title]');
        var messageEl = modalEl.querySelector('[data-confirm-modal-message]');
        var okBtn = modalEl.querySelector('[data-confirm-modal-ok]');
        var variantes = ['btn-danger', 'btn-warning', 'btn-success', 'btn-primary'];
        var formPendente = null;

        // Clique delegado: pega qualquer botão [data-confirm], inclusive os que
        // vierem por AJAX (scroll infinito).
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-confirm]');
            if (!trigger) return;

            e.preventDefault();
            formPendente = trigger.closest('form');

            titleEl.textContent = trigger.getAttribute('data-confirm-title') || 'Confirmar ação';
            messageEl.textContent = trigger.getAttribute('data-confirm-message') || 'Tem certeza?';
            okBtn.textContent = trigger.getAttribute('data-confirm-label') || 'Confirmar';

            variantes.forEach(function (v) { okBtn.classList.remove(v); });
            okBtn.classList.add('btn-' + (trigger.getAttribute('data-confirm-variant') || 'danger'));

            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        okBtn.addEventListener('click', function () {
            if (!formPendente) return;
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            formPendente.submit();
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            formPendente = null;
        });
    })();
</script>
@endsection

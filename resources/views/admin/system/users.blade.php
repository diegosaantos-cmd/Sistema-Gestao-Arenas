@extends('layouts.main')

@section('title', 'Clientes do Sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <x-back :href="route('admin.dashboard')" />

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
        <div class="table-responsive system-clients-container" style="max-height: 72vh; overflow-y: auto; padding-bottom: 18px;">
            <table class="table table-sm table-hover align-middle mb-0 small system-clients-table admin-sticky-table"
                   style="min-width: 1050px;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3">Cliente</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Nascimento</th>
                        <th>Termos</th>
                        <th>Cadastro</th>
                        <th>Situação</th>
                        <th class="text-end pe-3">Ações</th>
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
    /* No celular a tabela mostra TODAS as colunas e rola na horizontal (como as
       outras telas de listagem de clientes). A página rola na vertical — o
       scroll infinito usa a viewport no mobile. Nada de esconder colunas. */
    @media (max-width: 767.98px) {
        .system-clients-container {
            max-height: none !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
        }
        /* No celular o container vira um scroller horizontal; um cabeçalho
           "sticky" aí dentro flutua sobre as primeiras linhas. Então no celular
           ele NÃO é fixo — rola junto com a tabela. */
        .system-clients-table thead th {
            position: static !important;
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

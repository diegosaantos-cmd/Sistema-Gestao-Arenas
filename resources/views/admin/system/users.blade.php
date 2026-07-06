@extends('layouts.main')

@section('title', 'Clientes do Sistema')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm mb-3">← Voltar ao painel</a>

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Clientes do sistema</h1>
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
                           placeholder="Nome, e-mail ou telefone"
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const clientsBody = document.getElementById('systemClientsBody');

        clientsBody.addEventListener('click', function (event) {
            const button = event.target.closest('[data-client-mobile-details]');

            if (!button) {
                return;
            }

            const row = button.closest('tr');
            const detailsRow = row.nextElementSibling;
            const expanded = detailsRow.classList.contains('d-none');

            clientsBody.querySelectorAll('.client-mobile-details-row:not(.d-none)').forEach(function (openRow) {
                if (openRow !== detailsRow) {
                    openRow.classList.add('d-none');
                    const openButton = openRow.previousElementSibling
                        ?.querySelector('[data-client-mobile-details]');

                    if (openButton) {
                        openButton.textContent = 'Ver mais';
                    }
                }
            });

            detailsRow.classList.toggle('d-none', !expanded);
            button.textContent = expanded ? 'Fechar' : 'Ver mais';
        });
    });
</script>
@endsection

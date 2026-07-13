@extends('layouts.main')

@section('title', 'Detalhes da Empresa')

@section('content')

<div class="dashboard-container container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <x-back :href="route('admin.owners.index')" class="mb-0" />
        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2">
            @if ($owner->active)
                <button type="button" class="btn btn-warning btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalDesativarEmpresa">
                    <i class="bi bi-power me-1"></i> Desativar
                </button>
            @else
                <form method="POST" action="{{ route('admin.owners.activate', $owner) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle me-1"></i> Ativar
                    </button>
                </form>
            @endif
            <button type="button" class="btn btn-danger btn-sm"
                    data-bs-toggle="modal" data-bs-target="#modalExcluirEmpresa">
                <i class="bi bi-trash me-1"></i> Excluir
            </button>
        </div>
    </div>

    <div class="mb-4">
        <div class="text-muted text-uppercase fw-semibold small mb-1" style="letter-spacing:.05em;">Empresa</div>
        <h1 class="dashboard-title mb-1">{{ $owner->company_name }}</h1>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($owner->active)
                <span class="badge bg-success">Empresa ativa</span>
            @elseif ($owner->deactivation_source === 'admin')
                <span class="badge bg-danger">Desativada pelo administrador</span>
            @else
                <span class="badge bg-warning text-dark">Desativada pela própria empresa</span>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-6 col-lg-4">
            <button type="button" class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal" data-bs-target="#modalPerfilEmpresa">
                <div>
                    <h5 class="fw-bold text-dark">Perfil da empresa</h5>
                    <small class="text-muted">Dados completos</small>
                </div>
                <i class="bi bi-building-gear dashboard-icon text-primary"></i>
            </button>
        </div>

        <div class="col-6 col-lg-4">
            <button type="button" class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal" data-bs-target="#modalArenasEmpresa">
                <div>
                    <h5 class="fw-bold text-dark">Arenas</h5>
                    <h2>{{ $totais['arenas'] }}</h2>
                    <small class="text-muted">Todas as arenas da empresa</small>
                </div>
                <i class="bi bi-buildings dashboard-icon text-primary"></i>
            </button>
        </div>

        <div class="col-6 col-lg-4">
            <a href="{{ route('admin.owners.clients.page', $owner) }}"
               class="dashboard-card h-100 w-100 border-0 text-start text-decoration-none text-reset">
                <div>
                    <h5 class="fw-bold text-dark">Clientes</h5>
                    <h2>{{ $clientesEmpresa->total() }}</h2>
                    <small class="text-muted">Clientes de todas as arenas da empresa</small>
                </div>
                <i class="bi bi-people dashboard-icon text-primary"></i>
            </a>
        </div>

        <div class="col-6 col-lg-4">
            <button type="button" class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal" data-bs-target="#modalQuadrasEmpresa">
                <div>
                    <h5 class="fw-bold text-dark">Quadras</h5>
                    <h2>{{ $totais['quadras'] }}</h2>
                    <small class="text-muted">Todas as quadras das arenas da empresa</small>
                </div>
                <i class="bi bi-grid-3x3-gap dashboard-icon text-info"></i>
            </button>
        </div>

        <div class="col-6 col-lg-4">
            <button type="button" class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal" data-bs-target="#modalFuncionariosEmpresa">
                <div>
                    <h5 class="fw-bold text-dark">Funcionários</h5>
                    <h2>{{ $totais['funcionarios'] }}</h2>
                    <small class="text-muted">Todos os funcionários das arenas da empresa</small>
                </div>
                <i class="bi bi-person-badge dashboard-icon text-secondary"></i>
            </button>
        </div>

        <div class="col-6 col-lg-4">
            <button type="button" class="dashboard-card h-100 w-100 border-0 text-start"
                    data-bs-toggle="modal" data-bs-target="#modalFaturamentoEmpresa">
                <div>
                    <h5 class="fw-bold text-dark">Faturamento da empresa</h5>
                    <h2 class="fs-4 {{ $fatMesEmpresa['liquido'] < 0 ? 'text-danger' : '' }}">
                        R$ {{ number_format($fatMesEmpresa['liquido'], 2, ',', '.') }}
                    </h2>
                    <small class="text-muted">Líquido do mês (todas as arenas)</small>
                </div>
                <i class="bi bi-cash-coin dashboard-icon text-success"></i>
            </button>
        </div>
    </div>

</div>

<div class="modal fade" id="modalPerfilEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Perfil da empresa</h5>
                    <small class="text-muted">{{ $owner->company_name }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-6"><span class="text-muted">Nome da empresa</span><br><strong>{{ $owner->company_name }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Proprietário</span><br><strong>{{ $owner->user?->name ?? '—' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">CPF/CNPJ</span><br><strong>{{ $owner->tax_id }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Cadastro</span><br><strong>{{ optional($owner->created_at)->format('d/m/Y') ?? '—' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">E-mail</span><br><strong>{{ $owner->user?->email ?? '—' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Telefone</span><br><strong>{{ $owner->user?->phone ?: '—' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Tipo da conta</span><br><strong>Proprietário</strong></div>
                    <div class="col-md-6"><span class="text-muted">Quantidade de arenas</span><br><strong>{{ $totais['arenas'] }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Total de quadras</span><br><strong>{{ $totais['quadras'] }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Total de funcionários</span><br><strong>{{ $totais['funcionarios'] }}</strong></div>
                    <div class="col-md-6">
                        <span class="text-muted">Política de Privacidade e Termos</span><br>
                        @if ($owner->user?->terms_accepted_at)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Aceitos</span>
                            <div class="small text-muted mt-1">{{ $owner->user->terms_accepted_at->format('d/m/Y H:i') }}</div>
                        @else
                            <span class="badge bg-warning text-dark">Aceite não registrado</span>
                        @endif
                    </div>
                    @if (! $owner->active)
                        <div class="col-md-6">
                            <span class="text-muted">Desativada por</span><br>
                            <strong>{{ $owner->deactivation_source === 'admin' ? 'Administrador do sistema' : 'Própria empresa' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted">Data da desativação</span><br>
                            <strong>{{ optional($owner->deactivated_at)->format('d/m/Y H:i') ?? '—' }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalArenasEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title">Arenas da empresa</h5><small class="text-muted">{{ $owner->company_name }}</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    @forelse ($arenas as $arena)
                        <div class="col-6 col-lg-6">
                            <div class="border rounded h-100 p-3 d-flex flex-column">
                                <div class="d-flex justify-content-between gap-2 mb-2">
                                    <h6 class="fw-bold mb-0">{{ $arena->name }}</h6>
                                    <span class="badge {{ $arena->active ? 'bg-success' : 'bg-warning text-dark' }}">{{ $arena->active ? 'Ativa' : 'Desativada' }}</span>
                                </div>
                                <p class="small text-muted">{{ $arena->description ?: 'Sem descrição.' }}</p>
                                <div class="small mb-3">{{ $arena->address_rua }}, {{ $arena->address_numero }} — {{ $arena->address_bairro }}</div>
                                <a href="{{ route('admin.arenas.show', $arena) }}" class="btn btn-primary btn-sm mt-auto">Ver detalhes</a>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-4">Esta empresa não possui arenas.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalClientesEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title">Clientes da empresa</h5><small class="text-muted">{{ $owner->company_name }}</small></div>
                <div class="d-flex align-items-center justify-content-end gap-2 ms-auto">
                    <div class="collapse collapse-horizontal {{ request()->filled('busca_cliente') ? 'show' : '' }}"
                         id="buscaClientesEmpresa">
                        <form method="GET"
                              action="{{ route('admin.owners.show', $owner) }}"
                              data-client-search
                              data-endpoint="{{ route('admin.owners.clients', $owner) }}"
                              data-target="clientesEmpresaBody">
                            <input type="hidden" name="clientes_modal" value="1">
                            <input type="search"
                                   name="busca_cliente"
                                   value="{{ request('busca_cliente') }}"
                                   class="form-control form-control-sm"
                                   style="width: min(300px, 48vw);"
                                   placeholder="Nome, e-mail ou telefone"
                                   aria-label="Buscar cliente">
                        </form>
                    </div>
                    <button type="button" class="btn btn-link text-dark p-1"
                            data-bs-toggle="collapse" data-bs-target="#buscaClientesEmpresa"
                            aria-label="Abrir pesquisa"
                            aria-expanded="{{ request()->filled('busca_cliente') ? 'true' : 'false' }}">
                        <i class="bi bi-search fs-5"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="table-responsive border rounded" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0 small admin-sticky-table"
                           style="table-layout: fixed; width: 100%;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Cliente</th>
                                <th>E-mail</th>
                                <th>Telefone</th>
                                <th>Nascimento</th>
                                <th>Cadastro</th>
                                <th class="text-end">Reservas</th>
                                <th class="text-end pe-3">Total gasto</th>
                            </tr>
                        </thead>
                        <tbody id="clientesEmpresaBody">
                            @forelse ($clientesEmpresa as $cliente)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $cliente->user?->name ?? '—' }}</td>
                                    <td class="text-break">{{ $cliente->user?->email ?? '—' }}</td>
                                    <td class="text-break">{{ $cliente->user?->phone ?: '—' }}</td>
                                    <td>
                                        {{ $cliente->date_of_birth
                                            ? \Carbon\Carbon::parse($cliente->date_of_birth)->format('d/m/Y')
                                            : '—' }}
                                    </td>
                                    <td>{{ $cliente->user?->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="text-end fw-bold">{{ $cliente->reservas_na_empresa }}</td>
                                    <td class="text-end pe-3 fw-bold text-success">
                                        R$ {{ number_format($cliente->valor_total_na_empresa, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum cliente encontrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="text-center py-3 {{ $clientesEmpresa->hasMorePages() ? '' : 'd-none' }}"
                         data-infinite-clients
                         data-target="clientesEmpresaBody"
                         data-next-url="{{ $clientesEmpresa->hasMorePages()
                             ? route('admin.owners.clients', [
                                 $owner,
                                 'page' => 2,
                                 'busca_cliente' => request('busca_cliente'),
                             ])
                             : '' }}">
                        <span class="spinner-border spinner-border-sm text-primary d-none"
                              data-client-spinner></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalQuadrasEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title">Quadras da empresa</h5><small class="text-muted">{{ $owner->company_name }}</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive border rounded" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 admin-sticky-table">
                        <thead class="table-light sticky-top"><tr><th class="ps-3">Quadra</th><th>Arena</th><th>Esportes</th><th>Valor/hora</th><th class="pe-3">Situação</th></tr></thead>
                        <tbody>
                            @forelse (
                                $arenas->flatMap->courts->sortBy(
                                    fn ($quadra) => mb_strtolower(
                                        ($quadra->arena?->name ?? '') . '|' . $quadra->name
                                    )
                                ) as $quadra
                            )
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $quadra->name }}</td>
                                    <td>{{ $quadra->arena?->name ?? '—' }}</td>
                                    <td>{{ $quadra->sports->map(fn ($sport) => \App\Models\Court::SPORTS[$sport->sport] ?? $sport->sport)->implode(', ') ?: '—' }}</td>
                                    <td class="text-nowrap">R$ {{ number_format($quadra->hourly_rate, 2, ',', '.') }}</td>
                                    <td class="pe-3"><span class="badge {{ $quadra->active ? 'bg-success' : 'bg-warning text-dark' }}">{{ $quadra->active ? 'Ativa' : 'Desativada' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma quadra cadastrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFuncionariosEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 480px;">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title">Funcionários da empresa</h5><small class="text-muted">{{ $owner->company_name }}</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 align-items-start">
                    @forelse ($arenas->flatMap->employees->sortBy(fn ($employee) => $employee->user?->name) as $employee)
                        <div class="col-12">
                        <div class="border rounded p-3 h-100 d-flex flex-column" data-empresa-employee-card>
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <h6 class="fw-bold mb-1">{{ $employee->user?->name ?? 'Funcionário sem usuário' }}</h6>
                                    <div class="small">
                                        <strong>{{ $employee->position }}</strong>
                                        <span class="text-muted mx-1">·</span>
                                        {{ $employee->arena?->name ?? 'Arena não informada' }}
                                    </div>
                                </div>
                                <span class="badge {{ $employee->active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $employee->active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </div>

                            <div class="collapse my-3 w-100" id="detalhesFuncionarioEmpresa{{ $employee->id }}">
                                <div class="border-top pt-3">
                                    <div class="row g-3">
                                        @if ($employee->user?->email)
                                            <div class="col-12">
                                                <span class="small text-dark fw-bold">E-mail</span><br>
                                                <span class="text-break">{{ $employee->user->email }}</span>
                                            </div>
                                        @endif
                                        @if ($employee->user?->phone)
                                            <div class="col-6"><span class="small text-dark fw-bold">Telefone</span><br><span>{{ $employee->user->phone }}</span></div>
                                        @endif
                                        <div class="col-6"><span class="small text-dark fw-bold">Cargo</span><br><span>{{ $employee->position }}</span></div>
                                        <div class="col-6"><span class="small text-dark fw-bold">Arena</span><br><span>{{ $employee->arena?->name ?? 'Não informada' }}</span></div>
                                        <div class="col-6">
                                            <span class="small text-dark fw-bold">Nível de acesso</span><br>
                                            <span>{{ $employee->access_level === 'managerial' ? 'Gerencial' : 'Básico' }}</span>
                                        </div>
                                        <div class="col-6">
                                            <span class="small text-dark fw-bold">Situação</span><br>
                                            <span class="badge fw-normal {{ $employee->active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $employee->active ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </div>
                                        @if ($employee->created_at)
                                            <div class="col-6"><span class="small text-dark fw-bold">Cadastrado em</span><br><span>{{ $employee->created_at->format('d/m/Y H:i') }}</span></div>
                                        @endif
                                        @if ($employee->updated_at)
                                            <div class="col-6"><span class="small text-dark fw-bold">Última atualização</span><br><span>{{ $employee->updated_at->format('d/m/Y H:i') }}</span></div>
                                        @endif
                                        @if ($employee->arena)
                                            <div class="col-12">
                                                <button type="button" class="btn btn-danger btn-sm w-100"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalExcluirFuncionarioEmpresa{{ $employee->id }}">
                                                    <i class="bi bi-trash me-1"></i> Excluir
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary btn-sm w-100 mt-auto employee-details-toggle"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#detalhesFuncionarioEmpresa{{ $employee->id }}"
                                    aria-controls="detalhesFuncionarioEmpresa{{ $employee->id }}"
                                    aria-expanded="false">
                                Ver detalhes
                            </button>
                        </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-4">Nenhum funcionário cadastrado.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@foreach ($arenas->flatMap->employees as $employee)
    @if ($employee->arena)
        <div class="modal fade" id="modalExcluirFuncionarioEmpresa{{ $employee->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.arenas.employees.destroy', [$employee->arena, $employee]) }}">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title text-danger">Excluir funcionário</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            Deseja realmente excluir
                            <strong>{{ $employee->user?->name ?? 'este funcionário' }}</strong>
                            da Arena {{ $employee->arena->name }}?
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

<div class="modal fade" id="modalFaturamentoEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title">Faturamento da empresa</h5><small class="text-muted">{{ $owner->company_name }}</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.arenas._faturamento-detalhe', [
                    'fatMes' => $fatMesEmpresa,
                    'fatAcumulado' => $fatAcumuladoEmpresa,
                    'fatAno' => $fatAnoEmpresa,
                    'fatMensal' => $fatMensalEmpresa,
                    'anoFaturamento' => $anoFaturamentoEmpresa,
                    'anosFaturamento' => $anosFaturamentoEmpresa,
                    'formAction' => route('admin.owners.show', $owner),
                    'formHidden' => ['faturamento_modal' => 1],
                ])
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDesativarEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.owners.deactivate', $owner) }}">
                @csrf
                @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Desativar empresa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">Deseja realmente desativar <strong>{{ $owner->company_name }}</strong>? O proprietário perderá o acesso e todas as arenas e quadras serão desativadas.</div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning">Sim, desativar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExcluirEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.owners.destroy', $owner) }}">
                @csrf
                @method('DELETE')
                <div class="modal-header"><h5 class="modal-title text-danger">Excluir empresa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="alert alert-danger">Deseja realmente excluir <strong>{{ $owner->company_name }}</strong>?</div>A empresa e suas arenas deixarão de aparecer, mantendo o histórico.</div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Sim, excluir</button></div>
            </form>
        </div>
    </div>
</div>

@if (request()->boolean('arenas_modal'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalArenasEmpresa')).show();
        });
    </script>
@endif

@if (request()->boolean('faturamento_modal'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFaturamentoEmpresa')).show();
        });
    </script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let activeEmployeeDetails = null;

        document.querySelectorAll('#modalFuncionariosEmpresa .collapse').forEach(function (details) {
            const button = document.querySelector(
                `.employee-details-toggle[data-bs-target="#${details.id}"]`
            );

            if (!button) {
                return;
            }

            details.addEventListener('shown.bs.collapse', function () {
                if (activeEmployeeDetails && activeEmployeeDetails !== details) {
                    bootstrap.Collapse.getOrCreateInstance(
                        activeEmployeeDetails,
                        { toggle: false }
                    ).hide();
                }

                activeEmployeeDetails = details;
                button.textContent = 'Fechar detalhes';
            });

            details.addEventListener('hidden.bs.collapse', function () {
                button.textContent = 'Ver detalhes';

                if (activeEmployeeDetails === details) {
                    activeEmployeeDetails = null;
                }
            });
        });

        document.addEventListener('click', function (event) {
            if (!activeEmployeeDetails) {
                return;
            }

            const activeCard = activeEmployeeDetails.closest('[data-empresa-employee-card]');

            if (activeCard && !activeCard.contains(event.target)) {
                bootstrap.Collapse.getOrCreateInstance(
                    activeEmployeeDetails,
                    { toggle: false }
                ).hide();
            }
        });
    });
</script>

@endsection

@extends('layouts.main')

@section('title', 'Usuário — ' . $user->name)

@section('content')

@php
    $tipoLabel = ['client' => 'Cliente', 'employee' => 'Funcionário', 'owner' => 'Proprietário', 'admin' => 'Administrador'];
    $voltar = match ($user->type) {
        'client'   => route('admin.system.clients'),
        'employee' => route('admin.system.employees'),
        'admin'    => route('admin.system.administrators'),
        default    => route('admin.system.users'),
    };
@endphp

<div class="dashboard-container container-fluid py-4">
    <x-back :href="$voltar" />

    <div class="mb-4">
        <div class="text-muted text-uppercase fw-semibold small" style="letter-spacing:.05em;">
            {{ $tipoLabel[$user->type] ?? ucfirst($user->type) }}
        </div>
        <h1 class="dashboard-title mb-1">{{ $user->name }}</h1>
        <span class="badge {{ $user->active ? 'bg-success' : 'bg-danger' }}">
            {{ $user->active ? 'Ativo' : 'Bloqueado' }}
        </span>
    </div>

    {{-- Dados do usuário --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h2 class="h5 fw-bold mb-3">Dados do usuário</h2>
            <div class="row g-3">
                <div class="col-md-6"><div class="text-muted small">E-mail</div><div class="fw-semibold text-break">{{ $user->email }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Telefone</div><div class="fw-semibold">{{ $user->phone ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Tipo da conta</div><div class="fw-semibold">{{ $tipoLabel[$user->type] ?? ucfirst($user->type) }}</div></div>
                <div class="col-md-6">
                    <div class="text-muted small">Termos aceitos</div>
                    <div class="fw-semibold">
                        {{ $user->terms_accepted_at ? 'Sim (' . $user->terms_accepted_at->format('d/m/Y H:i') . ')' : 'Não registrado' }}
                    </div>
                </div>
                <div class="col-md-6"><div class="text-muted small">Cadastro</div><div class="fw-semibold">{{ optional($user->created_at)->format('d/m/Y H:i') ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Última atualização</div><div class="fw-semibold">{{ optional($user->updated_at)->format('d/m/Y H:i') ?? '—' }}</div></div>
            </div>
        </div>
    </div>

    {{-- Cliente --}}
    @if ($user->client)
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h5 fw-bold mb-3">Dados de cliente</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Data de nascimento</div>
                        <div class="fw-semibold">
                            {{ $user->client->date_of_birth ? \Carbon\Carbon::parse($user->client->date_of_birth)->format('d/m/Y') : '—' }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Total de reservas</div>
                        <div class="fw-semibold">{{ $reservas->total() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h5 fw-bold mb-3">Reservas do cliente</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nº</th>
                                <th>Data / Horário</th>
                                <th>Arena</th>
                                <th>Quadra</th>
                                <th>Status</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reservas as $r)
                                <tr>
                                    <td class="fw-semibold text-nowrap">#{{ $r->numeroDoCliente() }}</td>
                                    <td class="text-nowrap">
                                        {{ $r->date->format('d/m/Y') }}
                                        {{ substr($r->start_time, 0, 5) }}–{{ substr($r->end_time, 0, 5) }}
                                    </td>
                                    <td>{{ $r->court?->arena?->name ?? '—' }}</td>
                                    <td>{{ $r->court?->name ?? '—' }}</td>
                                    <td>
                                        @php
                                            $statusPt = [
                                                'pending' => 'Pendente',
                                                'confirmed' => 'Confirmada',
                                                'completed' => 'Concluída',
                                                'cancelled' => 'Cancelada',
                                            ];
                                        @endphp
                                        <span class="badge {{ $r->status === 'cancelled' ? 'bg-danger' : ($r->status === 'pending' ? 'bg-warning text-dark' : 'bg-success') }}">
                                            {{ $statusPt[$r->status] ?? ucfirst($r->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">R$ {{ number_format($r->total_amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">Nenhuma reserva.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $reservas->links() }}</div>
            </div>
        </div>
    @endif

    {{-- Funcionário --}}
    @if ($user->employee)
        @php $emp = $user->employee; @endphp
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h5 fw-bold mb-3">Dados de funcionário</h2>
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">Cargo</div><div class="fw-semibold">{{ $emp->position ?: '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Nível de acesso</div><div class="fw-semibold">{{ $emp->access_level === 'managerial' ? 'Gerencial' : 'Básico' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Arena</div><div class="fw-semibold">{{ $emp->arena?->name ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Empresa</div><div class="fw-semibold">{{ $emp->arena?->owner?->company_name ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Proprietário</div><div class="fw-semibold">{{ $emp->arena?->owner?->user?->name ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Cadastrado por</div><div class="fw-semibold">{{ $emp->createdBy?->name ?? '—' }}</div></div>
                </div>
            </div>
        </div>
    @endif

</div>

@endsection

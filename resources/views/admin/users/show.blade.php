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
                                <th>Pagamento</th>
                                <th class="text-end">Valor</th>
                                <th class="text-end">Taxa</th>
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
                                    <td>
                                        {{-- Pagamento: pago (com forma) / a pagar / atrasado; cancelada não se aplica --}}
                                        @php
                                            $sit = $r->situacaoPagamento();
                                            $pago = $r->payments->firstWhere('status', 'paid');
                                        @endphp
                                        @if ($sit === 'pago')
                                            <span class="badge bg-success">Pago</span>
                                            @if ($pago?->paymentMethod?->label)
                                                <div class="small text-muted">{{ $pago->paymentMethod->label }}</div>
                                            @endif
                                        @elseif ($sit === 'atrasado')
                                            <span class="badge bg-danger">Atrasado</span>
                                        @elseif ($sit === 'a_pagar')
                                            <span class="badge bg-warning text-dark">A pagar</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @if ($r->status === 'cancelled')
                                            {{-- Cancelada não gerou receita: valor riscado. --}}
                                            <span class="text-muted text-decoration-line-through">R$ {{ number_format($r->total_amount, 2, ',', '.') }}</span>
                                        @elseif ($pago && (float) $pago->discount_amount > 0)
                                            {{-- Destaque (cor) para o que ENTROU: o "pago" escuro; o valor cheio em cinza e riscado (não foi o que entrou). --}}
                                            <span class="text-muted text-decoration-line-through">R$ {{ number_format($r->total_amount, 2, ',', '.') }}</span>
                                            <div class="small text-danger">− R$ {{ number_format($pago->discount_amount, 2, ',', '.') }} desc.</div>
                                            <div class="small">pago R$ {{ number_format($pago->amount, 2, ',', '.') }}</div>
                                        @else
                                            R$ {{ number_format($r->total_amount, 2, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @if ($r->status === 'cancelled')
                                            @if ((float) $r->cancellation_fee_amount > 0)
                                                <span class="fw-semibold text-danger">R$ {{ number_format($r->cancellation_fee_amount, 2, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">Sem taxa</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-3">Nenhuma reserva.</td></tr>
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

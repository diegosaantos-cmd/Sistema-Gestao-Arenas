@extends('layouts.main')

@section('title', 'Sugestões e bugs')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <x-back :href="route('admin.dashboard')" />

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Sugestões e bugs</h1>
        <p class="dashboard-subtitle mb-0">Retornos enviados pelos usuários (clientes, donos, funcionários).</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="GET" class="mb-4 d-flex gap-2 flex-wrap align-items-center">
        <select name="tipo" class="form-select" style="max-width: 200px;">
            <option value="" @selected(! in_array($tipo, ['sugestao','bug']))>Todos os tipos</option>
            <option value="sugestao" @selected($tipo === 'sugestao')>Sugestões</option>
            <option value="bug" @selected($tipo === 'bug')>Bugs</option>
        </select>
        <select name="status" class="form-select" style="max-width: 200px;">
            <option value="" @selected(! in_array($status, ['aberto','em_andamento','resolvido']))>Todos os status</option>
            <option value="aberto" @selected($status === 'aberto')>Aberto</option>
            <option value="em_andamento" @selected($status === 'em_andamento')>Em andamento</option>
            <option value="resolvido" @selected($status === 'resolvido')>Resolvido</option>
        </select>
        <button class="btn btn-primary">Filtrar</button>
        @if ($tipo || $status)
            <a href="{{ route('admin.feedbacks') }}" class="btn btn-outline-secondary">Limpar</a>
        @endif
    </form>

    <div class="dashboard-box p-0 overflow-hidden">
        <div class="table-responsive" style="padding-bottom: 18px;">
            <table class="table table-sm table-hover align-middle mb-0 small" style="min-width: 940px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Usuário</th>
                        <th>Tipo</th>
                        <th>Assunto</th>
                        <th>Mensagem</th>
                        <th>Enviado</th>
                        <th class="pe-3" style="min-width: 170px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feedbacks as $f)
                        <tr>
                            <td class="ps-3 fw-semibold text-break"><x-nome-autor :user="$f->user" /></td>
                            <td>
                                <span class="badge {{ $f->tipo === 'bug' ? 'bg-danger' : 'bg-primary' }}">
                                    {{ $f->tipo === 'bug' ? 'Bug' : 'Sugestão' }}
                                </span>
                            </td>
                            <td class="fw-semibold">{{ $f->assunto }}</td>
                            <td>
                                <div style="width: 300px; overflow-wrap: anywhere; word-break: break-all; white-space: normal;">{{ $f->mensagem }}</div>
                            </td>
                            <td class="text-nowrap">{{ $f->created_at->format('d/m/Y H:i') }}</td>
                            <td class="pe-3">
                                <form method="POST" action="{{ route('admin.feedbacks.status', $f) }}" class="d-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="aberto" @selected($f->status === 'aberto')>Aberto</option>
                                        <option value="em_andamento" @selected($f->status === 'em_andamento')>Em andamento</option>
                                        <option value="resolvido" @selected($f->status === 'resolvido')>Resolvido</option>
                                    </select>
                                    <button class="btn btn-sm btn-success">OK</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum feedback recebido.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $feedbacks->links() }}</div>
</div>
@endsection

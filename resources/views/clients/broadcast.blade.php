@extends('layouts.main')

@section('title', 'Disparar mensagem — ' . $arena->name)

@section('content')

<div class="container py-4 painel">

    <x-back :href="route('clients.index')" />

    <h1 class="fw-bold mb-1">Disparar mensagem para vários clientes</h1>
    <p class="text-muted">
        Escreva a mensagem, selecione os clientes (todos ou específicos) e envie.
        A mensagem chega nas notificações do cliente e no e-mail.
    </p>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('clients.broadcast') }}">
        @csrf

        {{-- Mensagem --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Assunto</label>
                    <input type="text" name="title" class="form-control" maxlength="120" required
                           value="{{ old('title') }}" placeholder="Ex.: Promoção de horários">
                </div>
                <div class="mb-0">
                    <label class="form-label">Mensagem</label>
                    <textarea name="body" class="form-control" rows="4" maxlength="2000" required
                              placeholder="Escreva a mensagem para os clientes...">{{ old('body') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Seleção de clientes --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h5 fw-bold mb-3">Destinatários</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 1%;">
                                    <input type="checkbox" id="checkAll" class="form-check-input" title="Selecionar todos">
                                </th>
                                <th>Cliente</th>
                                <th>E-mail</th>
                                <th>Telefone</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clients as $client)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="client_ids[]" value="{{ $client->id }}"
                                               class="form-check-input js-client-check"
                                               {{ in_array($client->id, old('client_ids', [])) ? 'checked' : '' }}>
                                    </td>
                                    <td class="fw-semibold">{{ $client->user->name }}</td>
                                    <td>{{ $client->user->email }}</td>
                                    <td>{{ $client->user->phone ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        Nenhum cliente nesta arena.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-send me-1"></i> Disparar para os selecionados
            </button>
        </div>
    </form>

</div>

<script>
    (function () {
        var all = document.getElementById('checkAll');
        var boxes = document.querySelectorAll('.js-client-check');
        if (!all) return;

        all.addEventListener('change', function () {
            boxes.forEach(function (b) { b.checked = all.checked; });
        });

        boxes.forEach(function (b) {
            b.addEventListener('change', function () {
                if (!b.checked) all.checked = false;
            });
        });
    })();
</script>

@endsection

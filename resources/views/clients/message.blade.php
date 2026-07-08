@extends('layouts.main')

@section('title', 'Enviar mensagem — ' . ($client->user->name ?? ''))

@section('content')

<div class="container py-4">

    <a href="{{ route('clients.index') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar aos clientes
    </a>

    <h1 class="fw-bold mb-1">Enviar mensagem</h1>
    <p class="text-muted">
        Para <strong>{{ $client->user->name ?? '—' }}</strong> · {{ $client->user->email ?? '—' }}
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

    <div class="card shadow-sm border-0 mx-auto" style="max-width: 640px;">
        <div class="card-body">
            <p class="text-muted small">
                A mensagem chega ao cliente nas notificações dele dentro do sistema e no e-mail.
            </p>
            <form method="POST" action="{{ route('clients.message', $client) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Assunto</label>
                    <input type="text" name="title" class="form-control" maxlength="120" required
                           value="{{ old('title') }}" placeholder="Ex.: Pagamento pendente da sua reserva">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensagem</label>
                    <textarea name="body" class="form-control" rows="5" maxlength="2000" required
                              placeholder="Escreva a mensagem para o cliente...">{{ old('body') }}</textarea>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send me-1"></i> Enviar mensagem
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection

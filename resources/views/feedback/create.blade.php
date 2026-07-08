@extends('layouts.main')

@section('title', 'Sugestões e bugs')

@section('content')
<div class="container py-4" style="max-width: 760px;">

    <a href="{{ url()->previous() }}" class="btn btn-dark btn-sm mb-3">← Voltar</a>

    <h1 class="fw-bold mb-1">Sugestões e reporte de bugs</h1>
    <p class="text-muted">Ajude a melhorar o sistema: envie uma sugestão ou avise sobre um problema.</p>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('feedback.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label d-block">Tipo</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="tipo" id="tipoSugestao" value="sugestao"
                               {{ old('tipo', 'sugestao') === 'sugestao' ? 'checked' : '' }}>
                        <label class="btn btn-outline-primary" for="tipoSugestao">
                            <i class="bi bi-lightbulb me-1"></i> Sugestão
                        </label>

                        <input type="radio" class="btn-check" name="tipo" id="tipoBug" value="bug"
                               {{ old('tipo') === 'bug' ? 'checked' : '' }}>
                        <label class="btn btn-outline-danger" for="tipoBug">
                            <i class="bi bi-bug me-1"></i> Reportar bug
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assunto</label>
                    <input type="text" name="assunto" class="form-control" maxlength="120" required
                           value="{{ old('assunto') }}" placeholder="Ex.: Erro ao confirmar reserva">
                </div>

                <div class="mb-3">
                    <label class="form-label">Mensagem</label>
                    <textarea name="mensagem" class="form-control" rows="4" maxlength="2000" required
                              placeholder="Descreva sua sugestão ou o problema (o que você fez, o que aconteceu)...">{{ old('mensagem') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="bi bi-send me-1"></i> Enviar
                </button>
            </form>
        </div>
    </div>

    @if ($meus->isNotEmpty())
        <h2 class="h5 fw-bold mb-3">Seus envios recentes</h2>
        <div class="list-group">
            @foreach ($meus as $f)
                @php
                    $statusInfo = [
                        'aberto'       => ['Aberto', 'bg-secondary'],
                        'em_andamento' => ['Em andamento', 'bg-warning text-dark'],
                        'resolvido'    => ['Resolvido', 'bg-success'],
                    ][$f->status] ?? ['—', 'bg-secondary'];
                @endphp
                <div class="list-group-item">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <span class="badge {{ $f->tipo === 'bug' ? 'bg-danger' : 'bg-primary' }}">
                                {{ $f->tipo === 'bug' ? 'Bug' : 'Sugestão' }}
                            </span>
                            <strong class="ms-1">{{ $f->assunto }}</strong>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $statusInfo[1] }}">{{ $statusInfo[0] }}</span>
                            <span class="text-muted small">{{ $f->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($f->mensagem, 140) }}</div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection

@extends('layouts.main')

@section('title', 'Notificações')

@section('content')

<div class="container py-4" style="max-width: 800px;">

    <a href="{{ url('/dashboard') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar
    </a>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="fw-bold mb-0">Notificações</h1>
        @if ($notifications->whereNull('read_at')->isNotEmpty())
            <form method="POST" action="{{ route('notifications.readAll') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    Marcar todas como lidas
                </button>
            </form>
        @endif
    </div>

    @forelse ($notifications as $n)
        <a href="{{ route('notifications.show', $n) }}" class="text-decoration-none text-reset">
            <div class="card shadow-sm border-0 mb-2 card-hover {{ $n->read_at ? '' : 'border-start border-4 border-primary' }}">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="min-w-0">
                        <div class="fw-bold">
                            {{ $n->title }}
                            @if (! $n->read_at)
                                <span class="badge bg-primary ms-1">Nova</span>
                            @endif
                        </div>
                        <div class="text-muted small">
                            {{ \Illuminate\Support\Str::limit($n->body, 120) }}
                        </div>
                        @if ($n->arena)
                            <div class="text-muted" style="font-size: .75rem;">De: {{ $n->arena->name }}</div>
                        @endif
                    </div>
                    <div class="text-muted small text-nowrap">
                        {{ $n->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="alert alert-light border text-center text-muted">
            Você ainda não tem notificações.
        </div>
    @endforelse

</div>

@endsection

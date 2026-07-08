@extends('layouts.main')

@section('title', $notification->title)

@section('content')

<div class="container py-4" style="max-width: 720px;">

    <a href="{{ route('notifications.index') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar às notificações
    </a>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h1 class="h4 fw-bold mb-1">{{ $notification->title }}</h1>
            <div class="text-muted small mb-3">
                @if ($notification->arena)
                    {{ $notification->arena->name }} ·
                @endif
                {{ $notification->created_at->format('d/m/Y H:i') }}
            </div>
            <hr>
            <div style="white-space: pre-wrap;">{{ $notification->body }}</div>
        </div>
    </div>

</div>

@endsection

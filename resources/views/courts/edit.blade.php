@extends('layouts.main')

@section('title', 'Editar Quadra')

@section('content')

@php
    $sports = \App\Models\Court::SPORTS;
    $esportesAtuais = old('esportes', $quadra->sports->pluck('sport')->all());
@endphp

<div class="container py-5">

    <div class="card shadow mx-auto" style="max-width: 900px;">
        <div class="card-body p-4">

            <h2 class="mb-1">Editar Quadra</h2>
            <p class="text-muted">Arena: <strong>{{ $arena->name }}</strong></p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Não foi possível salvar.</strong> Corrija o que está marcado abaixo:
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('quadras.update', $quadra) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nome da quadra</label>
                    <input type="text" name="nome" class="form-control" maxlength="80"
                           value="{{ old('nome', $quadra->name) }}" required>
                </div>

                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor por hora (R$)</label>
                        <input type="number" name="valor_hora" class="form-control" step="0.01" min="0"
                               value="{{ old('valor_hora', $quadra->hourly_rate) }}" required>
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" name="ativa" value="1" class="form-check-input" id="ativa"
                                   {{ old('ativa', $quadra->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="ativa">Quadra ativa</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" class="form-control" rows="2"
                              placeholder="Piso, cobertura, iluminação...">{{ old('descricao', $quadra->description) }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label d-block">Esportes praticados</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach ($sports as $value => $label)
                            <div class="form-check">
                                <input type="checkbox" name="esportes[]" value="{{ $value }}"
                                       class="form-check-input" id="esporte_{{ $value }}"
                                       {{ in_array($value, $esportesAtuais) ? 'checked' : '' }}>
                                <label class="form-check-label" for="esporte_{{ $value }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('quadras.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>

            </form>

        </div>
    </div>

</div>

@endsection
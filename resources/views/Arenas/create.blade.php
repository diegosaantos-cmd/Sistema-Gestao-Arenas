@extends('layouts.main')

@section('content')

<div class="container py-5">

    <div class="card shadow mx-auto" style="max-width: 900px;">
        <div class="card-body p-4">

            <h1 class="h3 fw-bold mb-1">Nova arena</h1>
            <p class="text-muted">Preencha os dados, o funcionamento e as quadras da sua nova arena.</p>

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

            <form action="{{ route('arenas.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="nome">Nome da arena</label>
                        <input type="text" class="form-control" id="nome" name="nome"
                               value="{{ old('nome') }}" maxlength="120" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="descricao">
                            Descrição <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"
                                  placeholder="Conte o que sua arena tem de melhor.">{{ old('descricao') }}</textarea>
                    </div>

                    <div class="col-12 col-md-7">
                        <label class="form-label" for="rua">Rua</label>
                        <input type="text" class="form-control" id="rua" name="rua"
                               value="{{ old('rua') }}" maxlength="120" required>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label" for="numero">Número</label>
                        <input type="text" class="form-control" id="numero" name="numero"
                               value="{{ old('numero') }}" maxlength="15" required>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="bairro">Bairro</label>
                        <input type="text" class="form-control" id="bairro" name="bairro"
                               value="{{ old('bairro') }}" maxlength="100" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="telefone">Telefone de contato</label>
                        <input type="text" class="form-control" id="telefone" name="telefone"
                               value="{{ old('telefone') }}" data-mask="telefone" inputmode="numeric"
                               placeholder="(11) 3456-7890" maxlength="20" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="email_contato">E-mail de contato</label>
                        <input type="email" class="form-control" id="email_contato" name="email_contato"
                               value="{{ old('email_contato') }}" maxlength="150" required>
                    </div>
                </div>

                @include('arenas.partials.business-hours')

                @include('arenas.partials.courts')

                @include('arenas.partials.payment-methods')

                <hr class="my-4">
                @include('arenas.partials.cancellation-fee')

                <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-4">
                    <a href="{{ route('arenas.index') }}" class="btn btn-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Salvar
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

@endsection
@extends('layouts.main')

@section('content')

<div class="container py-5">

    <div class="card shadow mx-auto" style="max-width: 900px;">
        <div class="card-body p-4">

            <h2 class="mb-4">Nova Arena</h2>

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

                <div class="mb-3">
                    <input
                        type="text"
                        name="nome"
                        class="form-control form-control-lg"
                        placeholder="Nome da Arena"
                        required>
                </div>

                <div class="mb-3">
                    <textarea
                        name="descricao"
                        class="form-control"
                        rows="4"
                        placeholder="Descrição"></textarea>
                </div>

                <div class="mb-3">
                    <input
                        type="text"
                        name="rua"
                        class="form-control"
                        placeholder="Rua"
                        value="{{ old('rua') }}"
                        required>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <input
                            type="text"
                            name="bairro"
                            class="form-control"
                            placeholder="Bairro"
                            value="{{ old('bairro') }}"
                            required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <input
                            type="text"
                            name="numero"
                            class="form-control"
                            placeholder="Número"
                            value="{{ old('numero') }}"
                            required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input
                            type="text"
                            name="telefone"
                            class="form-control"
                            placeholder="Telefone">
                    </div>

                    <div class="col-md-6 mb-3">
                        <input
                            type="email"
                            name="email_contato"
                            class="form-control"
                            placeholder="Email de Contato">
                    </div>
                </div>

                @include('arenas.partials.business-hours')

                @include('arenas.partials.courts')

                @include('arenas.partials.payment-methods')

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('arenas.index') }}"
                       class="btn btn-secondary">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="btn btn-success">
                        Salvar
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

@endsection
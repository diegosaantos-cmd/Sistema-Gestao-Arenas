@extends('layouts.main')

@section('title', 'Detalhes da Arena')

@section('content')

<div class="container py-4">

    <a href="{{ route('owners.dashboard') }}" class="btn btn-dark btn-sm mb-3">
        ← Voltar ao painel
    </a>

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

    <!-- Cabeçalho + ações -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                {{ $arena->name }}
                @if ($arena->active)
                    <span class="badge bg-success align-middle">Ativa</span>
                @else
                    <span class="badge bg-secondary align-middle">Inativa</span>
                @endif
            </h1>
            <button type="button" class="btn btn-sm btn-outline-primary">✏️ Editar nome da arena</button>
        </div>

        {{-- Botões sem ação por enquanto (lógica depois) --}}
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-warning">
                {{ $arena->active ? '🚫 Desativar' : '✅ Reativar' }}
            </button>
            <button type="button" class="btn btn-danger">🗑️ Excluir</button>
        </div>
    </div>

    <div class="row g-4">

        <!-- Endereço + Contato -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Endereço &amp; Contato</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary">✏️ Editar</button>
                    </div>
                    <h6 class="fw-bold">Endereço</h6>
                    <p class="mb-1">{{ $arena->address_rua }}, {{ $arena->address_numero }}</p>
                    <p class="mb-0">{{ $arena->address_bairro }}</p>

                    <hr>

                    <h6 class="fw-bold">Contato</h6>
                    <p class="mb-1"><strong>Telefone:</strong> {{ $arena->phone ?? '—' }}</p>
                    <p class="mb-1"><strong>E-mail:</strong> {{ $arena->contact_email ?? '—' }}</p>
                    @if ($arena->description)
                        <p class="mb-0 mt-2"><strong>Descrição:</strong><br>{{ $arena->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Formas de pagamento -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    @php $idsSelecionados = $arena->paymentMethods->pluck('id')->all(); @endphp

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Formas de Pagamento</h5>
                        <button type="button" class="btn btn-sm btn-warning" id="btnEditarPagamentos">
                            ✏️ Editar
                        </button>
                    </div>

                    @if ($errors->has('pagamentos'))
                        <div class="alert alert-danger py-2">{{ $errors->first('pagamentos') }}</div>
                    @endif

                    {{-- Modo leitura --}}
                    <div id="pagamentosView">
                        @forelse ($arena->paymentMethods as $pm)
                            <span class="badge bg-light text-dark border me-1 mb-1">{{ $pm->label }}</span>
                        @empty
                            <span class="text-muted">Nenhuma cadastrada</span>
                        @endforelse
                    </div>

                    {{-- Modo edição --}}
                    <form method="POST" action="{{ route('arenas.payments.update', $arena->id) }}"
                          id="pagamentosForm" class="d-none">
                        @csrf
                        @method('PATCH')

                        @foreach ($todasFormasPagamento as $pm)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="pagamentos[]" value="{{ $pm->id }}"
                                       id="pm{{ $pm->id }}"
                                       {{ in_array($pm->id, old('pagamentos', $idsSelecionados)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="pm{{ $pm->id }}">{{ $pm->label }}</label>
                            </div>
                        @endforeach

                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-success btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#confirmPagamentos">
                                Salvar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancelarPagamentos">
                                Cancelar
                            </button>
                        </div>
                    </form>

                    {{-- Confirmação ao salvar --}}
                    <div class="modal fade" id="confirmPagamentos" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Alterar formas de pagamento</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                </div>
                                <div class="modal-body">
                                    As reservas <strong>já agendadas</strong> continuam com as formas de pagamento
                                    antigas. A mudança vale apenas para as <strong>novas reservas</strong>.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        Voltar
                                    </button>
                                    <button type="button" class="btn btn-success" id="btnConfirmarPagamentos">
                                        Continuar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Horário de funcionamento -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Horário de Funcionamento</h5>
                        <button type="button" class="btn btn-sm btn-warning" id="btnEditarHorarios">
                            ✏️ Editar
                        </button>
                    </div>

                    @php
                        $dias = [
                            0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira',
                            3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado',
                        ];
                        $porDia = $arena->businessHours->groupBy('day_of_week');
                    @endphp

                    {{-- Modo leitura --}}
                    <ul class="list-unstyled mb-0" id="horariosView">
                        @foreach ($dias as $num => $nome)
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span>{{ $nome }}</span>
                                <span>
                                    @if ($porDia->has($num))
                                        @foreach ($porDia[$num] as $h)
                                            {{ substr($h->opens_at, 0, 5) }}–{{ substr($h->closes_at, 0, 5) }}@if(! $loop->last), @endif
                                        @endforeach
                                    @else
                                        <span class="text-muted">Fechado</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Modo edição --}}
                    <form method="POST" action="{{ route('arenas.hours.update', $arena->id) }}"
                          id="horariosForm" class="d-none">
                        @csrf
                        @method('PATCH')

                        @include('arenas.partials.business-hours', ['horariosAtuais' => $horariosAtuais])

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-success btn-sm">Salvar</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancelarHorarios">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Quadras -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Quadras ({{ $arena->courts->count() }})</h5>
                        <a href="{{ route('quadras.index', ['from' => 'arena']) }}" class="btn btn-sm btn-outline-primary">✏️ Editar</a>
                    </div>

                    @forelse ($arena->courts as $court)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ $court->name }}</strong>
                                @if ($court->active)
                                    <span class="badge bg-success">Ativa</span>
                                @else
                                    <span class="badge bg-secondary">Inativa</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                <div><strong>Valor/hora:</strong> R$ {{ number_format($court->hourly_rate, 2, ',', '.') }}</div>
                                <div>
                                    <strong>Esportes:</strong>
                                    @if ($court->sports->isNotEmpty())
                                        @foreach ($court->sports as $s)
                                            {{ \App\Models\Court::SPORTS[$s->sport] ?? $s->sport }}@if(! $loop->last), @endif
                                        @endforeach
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <span class="text-muted">Nenhuma quadra cadastrada</span>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnEditar = document.getElementById('btnEditarPagamentos');
        const btnCancelar = document.getElementById('btnCancelarPagamentos');
        const view = document.getElementById('pagamentosView');
        const form = document.getElementById('pagamentosForm');

        function abrir() {
            view.classList.add('d-none');
            form.classList.remove('d-none');
            btnEditar.classList.add('d-none');
        }

        if (btnEditar) btnEditar.addEventListener('click', abrir);

        // Cancelar recarrega a página, voltando os checkboxes ao estado salvo.
        if (btnCancelar) {
            btnCancelar.addEventListener('click', function () {
                window.location.href = '{{ route('arenas.show', $arena->id) }}';
            });
        }

        // Continuar (modal) envia o formulário de pagamentos.
        const btnConfirmar = document.getElementById('btnConfirmarPagamentos');
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', function () {
                form.submit();
            });
        }

        @if ($errors->has('pagamentos'))
            abrir();
        @endif

        // --- Horários ---
        const btnEditarH = document.getElementById('btnEditarHorarios');
        const btnCancelarH = document.getElementById('btnCancelarHorarios');
        const viewH = document.getElementById('horariosView');
        const formH = document.getElementById('horariosForm');

        function abrirH() {
            viewH.classList.add('d-none');
            formH.classList.remove('d-none');
            btnEditarH.classList.add('d-none');
        }

        if (btnEditarH) btnEditarH.addEventListener('click', abrirH);

        // Cancelar recarrega a página, voltando os horários ao estado salvo.
        if (btnCancelarH) {
            btnCancelarH.addEventListener('click', function () {
                window.location.href = '{{ route('arenas.show', $arena->id) }}';
            });
        }

        @if (old('horarios'))
            abrirH();
        @endif
    });
</script>

@endsection

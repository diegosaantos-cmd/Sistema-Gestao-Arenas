@extends('layouts.main')

@section('title', 'Detalhes da Arena')

@section('content')

<div class="container py-4 painel">

    <x-back :href="route('owners.dashboard')" />

    {{-- Desativar/excluir/reativar a arena é só do dono; o gerente só edita. --}}
    @php $ehDono = \App\Support\ArenaAtual::ehDono(); @endphp

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
            {{-- Modo leitura --}}
            <div id="nomeView">
                <h1 class="fw-bold mb-1">
                    {{ $arena->name }}
                    @if ($arena->active)
                        <span class="badge bg-success align-middle">Ativa</span>
                    @else
                        <span class="badge bg-secondary align-middle">Inativa</span>
                    @endif
                </h1>
                <button type="button" class="btn btn-sm btn-warning" id="btnEditarNome">
                    ✏️ Editar nome da arena
                </button>
            </div>

            {{-- Modo edição --}}
            <form method="POST" action="{{ route('arenas.name.update', $arena->id) }}"
                  id="nomeForm" class="d-none">
                @csrf
                @method('PATCH')
                <label class="form-label fw-bold">Nome da arena</label>
                <div class="d-flex gap-2 flex-wrap align-items-start">
                    <input type="text" name="nome" class="form-control" style="max-width: 360px;"
                           value="{{ old('nome', $arena->name) }}" maxlength="120" required>
                    <button type="submit" class="btn btn-success btn-sm">Salvar</button>
                    <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarNome">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>

        @if ($ehDono)
        <div class="d-flex gap-2 flex-wrap">
            @if ($arena->active)
                <button type="button" class="btn btn-sm btn-secondary"
                        data-bs-toggle="modal" data-bs-target="#desativarArenaModal">
                    🚫 Desativar
                </button>

                {{-- Modal de confirmação de desativação --}}
                <div class="modal fade" id="desativarArenaModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Desativar arena</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                Ao desativar, a arena <strong>deixa de aparecer para novos agendamentos</strong>.
                                Se houver reservas futuras, você poderá informar o motivo do cancelamento na
                                próxima tela.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Voltar
                                </button>
                                <form method="POST" action="{{ route('arenas.toggle', $arena->id) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-danger">
                                        🚫 Sim, desativar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('arenas.toggle', $arena->id) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-success">✅ Reativar</button>
                </form>
            @endif

            <button type="button" class="btn btn-sm btn-danger"
                    data-bs-toggle="modal" data-bs-target="#excluirArenaModal">
                🗑️ Excluir
            </button>

            {{-- Modal de confirmação de exclusão --}}
            <div class="modal fade" id="excluirArenaModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Excluir arena</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            A arena será <strong>excluída</strong> e sairá da sua gestão e dos catálogos. Se houver reservas futuras,
                            você poderá informar o motivo do cancelamento na próxima tela.
                            <br><span class="text-danger">Esta ação não pode ser desfeita.</span>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Voltar
                            </button>
                            <form method="POST" action="{{ route('arenas.destroy', $arena->id) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    🗑️ Sim, excluir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="row g-4">

        <!-- Endereço + Contato -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Endereço &amp; Contato</h5>
                        <button type="button" class="btn btn-sm btn-warning" id="btnEditarContato">
                            ✏️ Editar
                        </button>
                    </div>

                    {{-- Modo leitura --}}
                    <div id="contatoView">
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

                    {{-- Modo edição --}}
                    <form method="POST" action="{{ route('arenas.contact.update', $arena->id) }}"
                          id="contatoForm" class="d-none">
                        @csrf
                        @method('PATCH')

                        <div class="row g-2">
                            <div class="col-8">
                                <label class="form-label">Rua</label>
                                <input type="text" name="rua" class="form-control"
                                       value="{{ old('rua', $arena->address_rua) }}" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label">Número</label>
                                <input type="text" name="numero" class="form-control"
                                       value="{{ old('numero', $arena->address_numero) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control"
                                       value="{{ old('bairro', $arena->address_bairro) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control"
                                       value="{{ old('telefone', $arena->phone) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email_contato" class="form-control"
                                       value="{{ old('email_contato', $arena->contact_email) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descrição</label>
                                <textarea name="descricao" class="form-control" rows="2"
                                          maxlength="300">{{ old('descricao', $arena->description) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-success btn-sm">Salvar</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarContato">
                                Cancelar
                            </button>
                        </div>
                    </form>
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
                            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarPagamentos">
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
                            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarHorarios">
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
                        <a href="{{ route('quadras.index', ['from' => 'arena']) }}" class="btn btn-sm btn-warning">✏️ Editar</a>
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

        <!-- Taxa de cancelamento -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Taxa de cancelamento</h5>
                        <button type="button" class="btn btn-sm btn-warning" id="btnEditarTaxa">
                            ✏️ Editar
                        </button>
                    </div>

                    {{-- Modo leitura --}}
                    <div id="taxaView">
                        @if (! $arena->charges_cancellation_fee)
                            <span class="text-muted">Esta arena <strong>não cobra</strong> taxa de cancelamento.</span>
                        @else
                            <p class="mb-1">
                                <strong>Valor:</strong>
                                @if ($arena->cancellation_fee_type === 'percent')
                                    {{ rtrim(rtrim(number_format($arena->cancellation_fee_value, 2, ',', '.'), '0'), ',') }}% do valor da reserva
                                @else
                                    R$ {{ number_format($arena->cancellation_fee_value, 2, ',', '.') }} (fixo)
                                @endif
                            </p>
                            <p class="mb-0">
                                <strong>Quando:</strong>
                                @if ($arena->cancellation_fee_mode === 'window')
                                    a partir de {{ $arena->cancellation_fee_window_hours }}h antes do início
                                    (grátis antes disso)
                                @else
                                    sempre que a reserva estiver confirmada
                                @endif
                            </p>
                        @endif
                    </div>

                    {{-- Modo edição --}}
                    <form method="POST" action="{{ route('arenas.fee.update', $arena->id) }}"
                          id="taxaForm" class="d-none">
                        @csrf
                        @method('PATCH')

                        @php
                            $taxaAtual = [
                                'charges' => $arena->charges_cancellation_fee,
                                'type' => $arena->cancellation_fee_type ?? 'fixed',
                                'value' => $arena->cancellation_fee_value,
                                'mode' => $arena->cancellation_fee_mode ?? 'always',
                                'hours' => $arena->cancellation_fee_window_hours,
                            ];
                        @endphp
                        @include('arenas.partials.cancellation-fee', ['taxaAtual' => $taxaAtual])

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-success btn-sm">Salvar</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarTaxa">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnEditarTaxa = document.getElementById('btnEditarTaxa');
        const btnCancelarTaxa = document.getElementById('btnCancelarTaxa');
        const taxaView = document.getElementById('taxaView');
        const taxaForm = document.getElementById('taxaForm');

        btnEditarTaxa?.addEventListener('click', function () {
            taxaView.classList.add('d-none');
            taxaForm.classList.remove('d-none');
            btnEditarTaxa.classList.add('d-none');
        });
        btnCancelarTaxa?.addEventListener('click', function () {
            window.location.href = '{{ route('arenas.show', $arena->id) }}';
        });
    });
</script>

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

        // --- Endereço & Contato ---
        const btnEditarC = document.getElementById('btnEditarContato');
        const btnCancelarC = document.getElementById('btnCancelarContato');
        const viewC = document.getElementById('contatoView');
        const formC = document.getElementById('contatoForm');

        function abrirC() {
            viewC.classList.add('d-none');
            formC.classList.remove('d-none');
            btnEditarC.classList.add('d-none');
        }

        if (btnEditarC) btnEditarC.addEventListener('click', abrirC);

        // Cancelar recarrega a página, voltando ao estado salvo.
        if (btnCancelarC) {
            btnCancelarC.addEventListener('click', function () {
                window.location.href = '{{ route('arenas.show', $arena->id) }}';
            });
        }

        @if (old('rua') !== null)
            abrirC();
        @endif

        // --- Nome da arena ---
        const btnEditarN = document.getElementById('btnEditarNome');
        const btnCancelarN = document.getElementById('btnCancelarNome');
        const viewN = document.getElementById('nomeView');
        const formN = document.getElementById('nomeForm');

        function abrirN() {
            viewN.classList.add('d-none');
            formN.classList.remove('d-none');
        }

        if (btnEditarN) btnEditarN.addEventListener('click', abrirN);

        if (btnCancelarN) {
            btnCancelarN.addEventListener('click', function () {
                window.location.href = '{{ route('arenas.show', $arena->id) }}';
            });
        }

        @if (old('nome') !== null)
            abrirN();
        @endif
    });
</script>

@endsection

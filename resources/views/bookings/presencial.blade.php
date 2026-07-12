@extends('layouts.main')

@section('title', 'Registrar reserva no balcão')

@section('content')
<div class="container py-4 painel">

    <x-back :href="route('owners.dashboard')" />

    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Registrar reserva</h1>
        <p class="text-muted mb-0">
            Para o cliente que chegou na arena <strong>{{ $arena->name }}</strong>.
            A reserva já entra <strong>confirmada</strong> e fica registrada em seu nome.
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Não foi possível registrar.</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Etapa 1: quadra e dia. Trocar qualquer um recarrega a grade de horários. --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">1. Quadra e dia</h2>

            <form method="GET" action="{{ route('bookings.presencial.create') }}" class="row g-3" data-recarregar>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="court_id">Quadra</label>
                    <select name="court_id" id="court_id" class="form-select min-w-0">
                        @foreach ($quadras as $q)
                            <option value="{{ $q->id }}" {{ $q->id === $quadra->id ? 'selected' : '' }}>
                                {{ $q->name }} — R$ {{ number_format($q->hourly_rate, 2, ',', '.') }}/hora
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label" for="date">Dia</label>
                    <input type="date" class="form-control" id="date" name="date"
                           value="{{ $data }}" min="{{ now()->toDateString() }}">
                </div>

                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button class="btn dashboard-btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Ver horários
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('bookings.presencial.store') }}">
        @csrf
        <input type="hidden" name="court_id" value="{{ $quadra->id }}">
        <input type="hidden" name="date" value="{{ $data }}">

        {{-- Etapa 2: horário --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">2. Horário(s)</h2>
                <p class="text-muted small mb-3">Pode marcar mais de um horário seguido (ex.: 2 horas).</p>

                @if (! $aberto)
                    <div class="alert alert-warning mb-0">
                        A arena não abre neste dia. Escolha outra data.
                    </div>
                @elseif ($slots->where('ocupado', false)->isEmpty())
                    <div class="alert alert-warning mb-0">
                        Não há horários livres nesta quadra neste dia.
                    </div>
                @else
                    {{-- Mesmo estilo dos horários na tela de reserva do cliente:
                         chips com borda; ocupado em azul-escuro riscado. Aqui é
                         seleção ÚNICA (rádio), e há o extra "já iniciado" do balcão. --}}
                    <div class="d-flex flex-wrap gap-3 mb-3 small text-muted">
                        <span><span class="d-inline-block border rounded me-1" style="width:14px;height:14px;vertical-align:middle;background:#fff;"></span> Livre</span>
                        <span><span class="d-inline-block rounded me-1" style="width:14px;height:14px;vertical-align:middle;background:#021B35;"></span> Ocupado</span>
                        @if ($slots->where('em_curso', true)->where('ocupado', false)->isNotEmpty())
                            <span><span class="d-inline-block border border-warning rounded me-1" style="width:14px;height:14px;vertical-align:middle;background:#fff3cd;"></span> Já iniciado</span>
                        @endif
                    </div>

                    @php $marcados = collect(old('horarios', [])); @endphp
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($slots as $slot)
                            @php $valor = $slot['start'].'-'.$slot['end']; @endphp

                            @if ($slot['ocupado'])
                                <span class="rounded px-3 py-2"
                                      style="background:#021B35; color:#fff; cursor:not-allowed; text-decoration:line-through;"
                                      title="Horário já reservado">
                                    {{ $slot['start'] }}–{{ $slot['end'] }}
                                </span>
                            @else
                                <label class="rounded px-3 py-2 {{ $slot['em_curso'] ? 'border border-warning' : 'border' }}"
                                       style="cursor: pointer; {{ $slot['em_curso'] ? 'background:#fff3cd;' : '' }}">
                                    <input type="checkbox" name="horarios[]" value="{{ $valor }}"
                                           class="me-1"
                                           {{ $marcados->contains($valor) ? 'checked' : '' }}>
                                    {{ $slot['start'] }}–{{ $slot['end'] }}
                                    @if ($slot['em_curso'])
                                        <span class="d-block small text-warning-emphasis">já iniciado</span>
                                    @endif
                                </label>
                            @endif
                        @endforeach
                    </div>

                    @if ($slots->where('em_curso', true)->where('ocupado', false)->isNotEmpty())
                        <div class="alert alert-light border small mt-3 mb-0">
                            <i class="bi bi-clock-history me-1"></i>
                            O horário marcado como <strong>já iniciado</strong> está em curso agora.
                            Ele só aparece aqui, no balcão — o cliente não consegue reservá-lo pelo site.
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Etapa 3: quem vai jogar --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">3. Quem vai jogar</h2>

                @php $tipo = old('tipo_cliente', 'avulso'); @endphp

                <div class="btn-group mb-3" role="group">
                    <input type="radio" class="btn-check" name="tipo_cliente" id="tipoAvulso"
                           value="avulso" {{ $tipo === 'avulso' ? 'checked' : '' }} data-tipo-cliente>
                    <label class="btn btn-outline-primary" for="tipoAvulso">Sem cadastro</label>

                    <input type="radio" class="btn-check" name="tipo_cliente" id="tipoCadastrado"
                           value="cadastrado" {{ $tipo === 'cadastrado' ? 'checked' : '' }} data-tipo-cliente>
                    <label class="btn btn-outline-primary" for="tipoCadastrado">Cliente cadastrado</label>
                </div>

                {{-- Cliente sem cadastro --}}
                <div data-bloco="avulso" class="{{ $tipo === 'avulso' ? '' : 'd-none' }}">
                    <div class="row g-3">
                        <div class="col-12 col-md-5">
                            <label class="form-label" for="guest_name">Nome</label>
                            <input type="text" class="form-control" id="guest_name" name="guest_name"
                                   value="{{ old('guest_name') }}" maxlength="120">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="guest_phone">Telefone</label>
                            <input type="text" class="form-control" id="guest_phone" name="guest_phone"
                                   value="{{ old('guest_phone') }}" data-mask="telefone" inputmode="numeric"
                                   placeholder="(11) 91234-5678" maxlength="20">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="guest_email">
                                E-mail <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <input type="email" class="form-control" id="guest_email" name="guest_email"
                                   value="{{ old('guest_email') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="form-text mt-2">
                        A reserva fica no histórico da arena. Como não há conta, o cliente não recebe aviso no app.
                    </div>
                </div>

                {{-- Cliente cadastrado --}}
                <div data-bloco="cadastrado" class="{{ $tipo === 'cadastrado' ? '' : 'd-none' }}">
                    <label class="form-label" for="buscaCliente">Buscar por nome ou e-mail</label>
                    <input type="search" class="form-control mb-2" id="buscaCliente"
                           placeholder="Digite para filtrar a lista" data-filtro-cliente>

                    {{-- Lista rolável, UM cliente por vez (rádio). Cada item mostra
                         nome e e-mail inteiros (sem truncar) e rola no celular. --}}
                    <div class="border rounded cliente-lista" data-lista-cliente>
                        @forelse ($clientes as $c)
                            <label class="d-flex align-items-start gap-2 p-2 border-bottom cliente-opcao"
                                   data-busca="{{ mb_strtolower($c->user->name.' '.$c->user->email) }}">
                                <input type="radio" name="client_id" value="{{ $c->id }}"
                                       class="form-check-input flex-shrink-0 mt-1"
                                       {{ (int) old('client_id') === $c->id ? 'checked' : '' }}>
                                <span class="min-w-0">
                                    <span class="d-block fw-semibold text-break">{{ $c->user->name }}</span>
                                    <span class="d-block small text-muted text-break">{{ $c->user->email }}</span>
                                </span>
                            </label>
                        @empty
                            <div class="p-3 text-muted small mb-0">Nenhum cliente cadastrado ainda.</div>
                        @endforelse
                    </div>

                    <div class="form-text mt-2">
                        O cliente recebe o aviso de reserva confirmada no sino e por e-mail.
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
            <a href="{{ route('owners.dashboard') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle me-1"></i> Registrar reserva
            </button>
        </div>
    </form>
</div>

<script src="/js/masks.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Trocar quadra ou dia recarrega a grade de horários.
        document.querySelectorAll('[data-recarregar] select, [data-recarregar] input')
            .forEach(function (campo) {
                campo.addEventListener('change', function () { campo.form.submit(); });
            });

        // Alterna entre "sem cadastro" e "cliente cadastrado".
        document.querySelectorAll('[data-tipo-cliente]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.querySelectorAll('[data-bloco]').forEach(function (bloco) {
                    bloco.classList.toggle('d-none', bloco.dataset.bloco !== radio.value);
                });
            });
        });

        // Filtra a lista de clientes cadastrados por nome ou e-mail.
        var busca = document.querySelector('[data-filtro-cliente]');
        var lista = document.querySelector('[data-lista-cliente]');

        if (busca && lista) {
            var itens = Array.prototype.slice.call(lista.querySelectorAll('[data-busca]'));

            busca.addEventListener('input', function () {
                var termo = busca.value.trim().toLowerCase();

                itens.forEach(function (item) {
                    var achou = termo === '' || item.dataset.busca.indexOf(termo) !== -1;
                    item.classList.toggle('d-none', !achou);
                });
            });
        }

        // Permite DESmarcar o cliente: clicar no rádio já selecionado limpa a
        // escolha (o rádio nativo não desmarca sozinho). Delegação no container:
        // pega o clique direto no rádio e o encaminhado pelo <label>, sem duplicar.
        if (lista) {
            var selecionado = (lista.querySelector('input[name="client_id"]:checked') || {}).value || null;

            lista.addEventListener('click', function (e) {
                var radio = e.target.closest('input[type="radio"][name="client_id"]');
                if (!radio) return;

                if (selecionado === radio.value) {
                    radio.checked = false;       // já estava marcado -> desmarca
                    selecionado = null;
                } else {
                    selecionado = radio.value;   // marcou outro cliente
                }
            });
        }
    });
</script>
@endsection

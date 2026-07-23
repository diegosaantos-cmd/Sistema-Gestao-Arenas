@extends('layouts.main')

@section('title', 'Reservas — ' . $arena->name)

@section('content')
@php
    $statusReservas = [
        'pending'   => ['Pendente',   'bg-warning text-dark'],
        'confirmed' => ['Confirmada', 'bg-success'],
        'completed' => ['Concluída',  'bg-success'],
        'cancelled' => ['Cancelada',  'bg-danger'],
    ];
@endphp

<div class="dashboard-container container-fluid py-4">
    <x-back :href="route('admin.arenas.show', [$arena, 'origem' => request('origem')])" />

    <div class="mb-4">
        <div class="text-muted text-uppercase fw-semibold small" style="letter-spacing:.05em;">Arena {{ $arena->name }}</div>
        <h1 class="dashboard-title mb-1">
            Reservas da arena
            {{-- Total de todos os meses: separa o acervo inteiro do que as abas
                 (filtradas pelo mês) mostram. --}}
            <span class="badge bg-secondary align-middle fs-6">{{ $reservasTotalArena }} no total</span>
        </h1>
        <p class="dashboard-subtitle mb-0">Reservas, pagamentos atrasados e cancelamentos do mês escolhido.</p>
    </div>

    <form method="GET" class="mb-3 d-flex gap-2 flex-wrap" style="max-width: 720px;">
        <input type="hidden" name="origem" value="{{ request('origem') }}">
        <input type="hidden" name="aba_reservas" value="{{ $aba }}">
        {{-- Preserva o mês escolhido ao buscar/ordenar. --}}
        <input type="hidden" name="mes" value="{{ $mesRef->format('Y-m') }}">
        <input type="text" name="busca_reserva" value="{{ $busca }}"
               class="form-control" style="max-width: 300px;" placeholder="Cliente ou data (dd/mm/aaaa)">
        <select name="ordenar_reserva" class="form-select" style="max-width: 230px;"
                title="Ordenar as reservas" onchange="this.form.submit()">
            @foreach (\App\Models\Booking::ORDENS as $chave => $rotulo)
                <option value="{{ $chave }}" @selected(($ordenar ?? \App\Models\Booking::ORDEM_PADRAO) === $chave)>{{ $rotulo }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Buscar</button>
        @if ($busca)
            <a href="{{ route('admin.arenas.reservas', [$arena, 'origem' => request('origem')]) }}" class="btn btn-outline-secondary">Limpar</a>
        @endif
    </form>

    {{-- Com busca ativa, a página procura em TODOS os meses (a busca vale mais
         que o mês, ver arenaReservasPage). O navegador de mês some, porque não
         teria efeito, e um aviso explica por que os resultados atravessam meses. --}}
    @if ($temBusca)
        <div class="alert alert-info d-flex align-items-center gap-2 mb-3 py-2">
            <i class="bi bi-search"></i>
            <span>Busca ativa — mostrando resultados de <strong>todos os meses</strong>. Limpe a busca para voltar a navegar por mês.</span>
        </div>
    @else
    {{-- Navegação por mês em nível de PÁGINA: filtra as três abas ao mesmo
         tempo (reservas, atrasados e canceladas do mês). Fica aqui, e não
         dentro de uma aba, para dar para trocar o mês esteja em qual aba
         estiver. Cada link preserva a ordem e a aba ativa; array_filter tira os
         parâmetros vazios da URL. --}}
    @php
        $baseMes = array_filter([
            'origem' => request('origem'),
            'ordenar_reserva' => $ordenar,
            'aba_reservas' => $aba,
        ]);
    @endphp
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <span class="text-muted small fw-semibold">Mês:</span>

        {{-- Setas, campo e "Ir" juntos num input-group: assim o grupo
             ‹ [campo] Ir › fica coeso e encolhe como uma peça só, em vez de as
             setas quebrarem para longe do campo em telas estreitas.

             As setas são links (recua/avança um mês); dentro de um <form>, o
             clique num <a> navega pelo href e NÃO envia o formulário. O campo
             não se envia sozinho — confirma pelo "Ir" ou Enter. --}}
        <form method="GET" class="m-0">
            @foreach ($baseMes as $nome => $valor)
                <input type="hidden" name="{{ $nome }}" value="{{ $valor }}">
            @endforeach
            <div class="input-group input-group-sm flex-nowrap" style="width: auto;">
                <a href="{{ route('admin.arenas.reservas', array_merge([$arena], $baseMes, ['mes' => $mesRef->copy()->subMonth()->format('Y-m')])) }}"
                   class="btn btn-outline-secondary" title="Mês anterior" aria-label="Mês anterior">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <input type="month" name="mes" value="{{ $mesRef->format('Y-m') }}"
                       class="form-control" style="max-width: 150px;" aria-label="Escolher mês e ano">
                <button type="submit" class="btn btn-primary" title="Ir para o mês">Ir</button>
                <a href="{{ route('admin.arenas.reservas', array_merge([$arena], $baseMes, ['mes' => $mesRef->copy()->addMonth()->format('Y-m')])) }}"
                   class="btn btn-outline-secondary" title="Próximo mês" aria-label="Próximo mês">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </form>

        @unless ($mesRef->isSameMonth(now()))
            <a href="{{ route('admin.arenas.reservas', array_merge([$arena], $baseMes)) }}"
               class="btn btn-sm btn-link text-decoration-none" title="Voltar ao mês atual">
                Mês atual
            </a>
        @endunless
    </div>
    @endif

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button class="nav-link {{ $aba === 'mes' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#abaMes" type="button">
                {{ $temBusca ? 'Reservas encontradas' : 'Reservas do mês' }}
                <span class="badge bg-secondary ms-1">{{ $reservasMesLista->total() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-warning {{ $aba === 'atrasados' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#abaAtrasados" type="button">
                Pagamentos atrasados <span class="badge bg-warning text-dark ms-1">{{ $reservasAtrasadasLista->total() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-danger {{ $aba === 'canceladas' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#abaCanceladas" type="button">
                Canceladas <span class="badge bg-danger ms-1">{{ $reservasCanceladasLista->total() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $aba === 'mes' ? 'show active' : '' }}" id="abaMes">
            @include('admin.arenas._booking-list', ['listaReservas' => $reservasMesLista, 'mostrarTaxa' => true])
        </div>
        <div class="tab-pane fade {{ $aba === 'atrasados' ? 'show active' : '' }}" id="abaAtrasados">
            @include('admin.arenas._booking-list', ['listaReservas' => $reservasAtrasadasLista])
        </div>
        <div class="tab-pane fade {{ $aba === 'canceladas' ? 'show active' : '' }}" id="abaCanceladas">
            @include('admin.arenas._booking-list', ['listaReservas' => $reservasCanceladasLista, 'mostrarTaxa' => true])
        </div>
    </div>
</div>
@endsection

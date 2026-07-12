@extends('layouts.main')

@section('title', 'Reservas a receber')

@section('content')

<div class="container py-4 painel">

    <x-back :href="route('caixa.index')" />

    <h1 class="fw-bold mb-1">Reservas a receber</h1>
    <p class="text-muted">Reservas confirmadas desta arena ainda sem pagamento.</p>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Origem</th>
                            <th>Quadra</th>
                            <th>Data / Horário</th>
                            <th>Situação</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservasAReceber as $reserva)
                            @php
                                $fimReserva = \Carbon\Carbon::parse($reserva->date->toDateString() . ' ' . $reserva->end_time);
                                $jaPassou = now()->greaterThan($fimReserva);
                            @endphp
                            <tr>
                                <td>{{ $reserva->nomeCliente() }}</td>
                                <td>@include('partials.origin-badge', ['booking' => $reserva])</td>
                                <td>{{ $reserva->court->name ?? '—' }}</td>
                                <td>
                                    {{ $reserva->date->format('d/m/Y') }}
                                    {{ substr($reserva->start_time, 0, 5) }}–{{ substr($reserva->end_time, 0, 5) }}
                                </td>
                                <td>
                                    @if ($jaPassou)
                                        <span class="badge bg-danger">Realizada não paga</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Agendada</span>
                                    @endif
                                </td>
                                <td class="text-end">R$ {{ number_format($reserva->total_amount, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-success"
                                            data-bs-toggle="modal" data-bs-target="#receber{{ $reserva->id }}">
                                        💵 Receber
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Nenhuma reserva pendente de pagamento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Modais de receber (um por reserva) --}}
@foreach ($reservasAReceber as $reserva)
    <div class="modal fade" id="receber{{ $reserva->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('caixa.pay', $reserva) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Receber reserva #{{ $numerosReservas[$reserva->id] ?? $reserva->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1"><strong>Cliente:</strong> {{ $reserva->nomeCliente() }}</p>
                    <p class="mb-3">
                        <strong>Quadra:</strong> {{ $reserva->court->name ?? '—' }} —
                        {{ $reserva->date->format('d/m/Y') }}
                        {{ substr($reserva->start_time, 0, 5) }}–{{ substr($reserva->end_time, 0, 5) }}
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Forma de pagamento</label>
                        <select name="payment_method_id" class="form-select js-forma-caixa" required>
                            <option value="">Selecione…</option>
                            @foreach ($formasPagamento as $forma)
                                <option value="{{ $forma->id }}" data-type="{{ $forma->type }}">{{ $forma->label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted">Valor a receber (fixo da reserva):</span>
                        <div class="fs-4 fw-bold">R$ {{ number_format($reserva->total_amount, 2, ',', '.') }}</div>
                    </div>

                    {{-- PIX (simulação — o cliente paga na hora) --}}
                    <div data-forma="pix" class="border rounded p-3 mb-0 bg-light text-center">
                        <div class="fw-bold mb-2">Pagamento com PIX</div>
                        <div class="mx-auto mb-2 d-flex align-items-center justify-content-center border rounded bg-white"
                             style="width:150px;height:150px;">
                            <i class="bi bi-qr-code" style="font-size:5.5rem;"></i>
                        </div>
                        <div class="small text-muted">PIX copia e cola:</div>
                        <code class="d-block text-truncate">PIX-SIMULACAO-RESERVA-{{ $reserva->id }}-{{ number_format($reserva->total_amount, 2, '', '') }}</code>
                        <div class="alert alert-warning small mt-2 mb-0">Simulação — nenhuma cobrança bancária real é feita.</div>
                    </div>

                    {{-- Cartão (simulação — o cliente paga na hora) --}}
                    <div data-forma="card" class="border rounded p-3 mb-0 bg-light">
                        <div class="fw-bold mb-2">Pagamento com cartão</div>
                        <input type="text" class="form-control mb-2" placeholder="Número do cartão" inputmode="numeric" maxlength="19" autocomplete="off">
                        <div class="row g-2">
                            <div class="col"><input type="text" class="form-control" placeholder="Validade (MM/AA)" autocomplete="off"></div>
                            <div class="col"><input type="text" class="form-control" placeholder="CVV" maxlength="4" inputmode="numeric" autocomplete="off"></div>
                        </div>
                        <div class="alert alert-warning small mt-2 mb-0">Simulação — os dados não são enviados nem cobrados.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar pagamento</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<script>
    (function () {
        // Em cada modal de "Receber", mostra a simulação (PIX/cartão) conforme
        // a forma escolhida. Dinheiro não tem simulação — recebe direto.
        document.querySelectorAll('.js-forma-caixa').forEach(function (sel) {
            var form = sel.closest('form');
            var blocks = form.querySelectorAll('[data-forma]');

            function sync() {
                var opt = sel.options[sel.selectedIndex];
                var type = opt ? opt.getAttribute('data-type') : '';
                blocks.forEach(function (b) {
                    b.style.display = (b.getAttribute('data-forma') === type) ? '' : 'none';
                });
            }

            sel.addEventListener('change', sync);
            sync();
        });
    })();
</script>

@endsection
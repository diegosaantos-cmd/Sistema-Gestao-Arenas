@extends('layouts.main')

@section('title', 'Pagar reserva')

@section('content')

<div class="container py-4">

    <x-back :href="route($origem ?? 'client.bookings.index')" />

    <h1 class="fw-bold mb-1">Pagar reserva #{{ $numeroReserva ?? $booking->id }}</h1>
    <p class="text-muted">
        {{ $booking->court->arena->name ?? '—' }} · Quadra <strong>{{ $booking->court->name ?? '—' }}</strong> ·
        {{ $booking->date->format('d/m/Y') }}
        {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
    </p>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mx-auto" style="max-width: 640px;">
        <div class="card-body">

            <div class="text-center mb-4">
                <div class="text-muted">Valor a pagar</div>
                <div class="fs-2 fw-bold">R$ {{ number_format($booking->total_amount, 2, ',', '.') }}</div>
            </div>

            <form method="POST" action="{{ route('client.bookings.pay.confirm', $booking) }}">
                @csrf
                <input type="hidden" name="origem" value="{{ $origem ?? '' }}">
                @php $atual = old('payment_method', $booking->paymentMethod?->type); @endphp

                <div class="mb-3">
                    <label class="form-label">Forma de pagamento</label>
                    <select name="payment_method" id="formaPagamento" class="form-select" required>
                        @foreach ($formas as $f)
                            <option value="{{ $f->type }}" {{ $atual === $f->type ? 'selected' : '' }}>{{ $f->label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- PIX (simulação) --}}
                <div data-forma="pix" class="border rounded p-3 mb-3 bg-light text-center">
                    <div class="fw-bold mb-2">Pague com PIX</div>
                    <div class="mx-auto mb-2 d-flex align-items-center justify-content-center border rounded bg-white"
                         style="width:160px;height:160px;">
                        <i class="bi bi-qr-code" style="font-size:6rem;"></i>
                    </div>
                    <div class="small text-muted">PIX copia e cola:</div>
                    <code class="d-block text-truncate">PIX-SIMULACAO-RESERVA-{{ $booking->id }}-{{ number_format($booking->total_amount, 2, '', '') }}</code>
                    <div class="alert alert-warning small mt-2 mb-0">Simulação — nenhuma cobrança bancária real é feita.</div>
                </div>

                {{-- Cartão (simulação) --}}
                <div data-forma="card" class="border rounded p-3 mb-3 bg-light">
                    <div class="fw-bold mb-2">Cartão</div>
                    <input type="text" class="form-control mb-2" placeholder="Número do cartão" inputmode="numeric" maxlength="19" autocomplete="off">
                    <div class="row g-2">
                        <div class="col"><input type="text" class="form-control" placeholder="Validade (MM/AA)" autocomplete="off"></div>
                        <div class="col"><input type="text" class="form-control" placeholder="CVV" maxlength="4" inputmode="numeric" autocomplete="off"></div>
                    </div>
                    <div class="alert alert-warning small mt-2 mb-0">Simulação — os dados não são enviados nem cobrados.</div>
                </div>

                {{-- Dinheiro --}}
                <div data-forma="cash" class="border rounded p-3 mb-3 bg-light">
                    <div class="fw-bold mb-1">Dinheiro</div>
                    <div class="small text-muted mb-0">
                        Você paga <strong>diretamente na arena</strong> ao usar o horário —
                        não precisa confirmar nada aqui. É só voltar.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route($origem ?? 'client.bookings.index') }}" class="btn btn-secondary">Voltar</a>
                    <button type="submit" class="btn btn-success" id="btnPagar">Confirmar pagamento</button>
                </div>
            </form>

        </div>
    </div>

</div>

<script>
    (function () {
        var sel = document.getElementById('formaPagamento');
        var blocks = document.querySelectorAll('[data-forma]');
        var btn = document.getElementById('btnPagar');
        if (!sel) return;

        function sync() {
            var v = sel.value;
            blocks.forEach(function (b) {
                b.style.display = (b.getAttribute('data-forma') === v) ? '' : 'none';
            });
            // Dinheiro: paga na arena -> não mostra o botão de confirmar.
            btn.style.display = (v === 'cash') ? 'none' : '';
        }
        sel.addEventListener('change', sync);
        sync();
    })();
</script>

@endsection

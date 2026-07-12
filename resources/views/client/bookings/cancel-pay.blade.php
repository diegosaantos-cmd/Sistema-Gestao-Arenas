@extends('layouts.main')

@section('title', 'Cancelar reserva')

@section('content')

<div class="container py-4">

    <x-back :href="route('client.bookings.index')" />

    <h1 class="fw-bold mb-1">Cancelar reserva #{{ $numeroReserva ?? $booking->id }}</h1>
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

    <div class="alert alert-warning">
        Para cancelar esta reserva é preciso <strong>pagar a taxa de cancelamento</strong>
        (PIX ou cartão). Se você <strong>não pagar, a reserva não é cancelada</strong>.
    </div>

    <div class="card shadow-sm border-0 mx-auto" style="max-width: 640px;">
        <div class="card-body">

            <div class="text-center mb-4">
                <div class="text-muted">Taxa de cancelamento</div>
                <div class="fs-2 fw-bold text-danger">R$ {{ number_format($taxa, 2, ',', '.') }}</div>
            </div>

            @if ($formas->isEmpty())
                <div class="alert alert-secondary mb-0">
                    Esta arena não tem PIX nem cartão disponíveis para pagar a taxa online.
                    Fale com a arena para cancelar.
                </div>
            @else
                <form method="POST" action="{{ route('client.bookings.cancel-pay.confirm', $booking) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Motivo do cancelamento</label>
                        <textarea name="motivo" class="form-control" rows="2" maxlength="255" required
                                  placeholder="Ex.: Não vou poder comparecer.">{{ old('motivo') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Forma de pagamento da taxa</label>
                        <select name="payment_method" id="formaTaxa" class="form-select" required>
                            @foreach ($formas as $f)
                                <option value="{{ $f->type }}" {{ old('payment_method') === $f->type ? 'selected' : '' }}>{{ $f->label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- PIX (simulação) --}}
                    <div data-forma="pix" class="border rounded p-3 mb-3 bg-light text-center">
                        <div class="fw-bold mb-2">Pague a taxa com PIX</div>
                        <div class="mx-auto mb-2 d-flex align-items-center justify-content-center border rounded bg-white"
                             style="width:150px;height:150px;">
                            <i class="bi bi-qr-code" style="font-size:5.5rem;"></i>
                        </div>
                        <code class="d-block text-truncate">PIX-TAXA-RESERVA-{{ $booking->id }}</code>
                        <div class="alert alert-warning small mt-2 mb-0">Simulação — nenhuma cobrança real é feita.</div>
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

                    <div class="d-flex gap-2">
                        <a href="{{ route('client.bookings.index') }}" class="btn btn-secondary">Voltar (não cancelar)</a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i> Pagar taxa e cancelar
                        </button>
                    </div>
                </form>
            @endif

        </div>
    </div>

</div>

<script>
    (function () {
        var sel = document.getElementById('formaTaxa');
        var blocks = document.querySelectorAll('[data-forma]');
        if (!sel) return;
        function sync() {
            var v = sel.value;
            blocks.forEach(function (b) {
                b.style.display = (b.getAttribute('data-forma') === v) ? '' : 'none';
            });
        }
        sel.addEventListener('change', sync);
        sync();
    })();
</script>

@endsection

{{--
    Aviso mostrado no modal de cancelamento do STAFF quando a reserva JÁ está paga.
    Quem cancela pela arena reembolsa o cliente INTEGRALMENTE (sem taxa). A forma de
    reembolso muda conforme como foi pago (dinheiro = devolução física; pix/cartão =
    estorno). Uso:
        @include('partials.aviso-reembolso-cancelamento', ['booking' => $booking])
--}}
@php
    $booking->loadMissing('payments.paymentMethod');
    $pagoAviso = $booking->isPaga() ? $booking->payments->firstWhere('status', 'paid') : null;
@endphp
@if ($pagoAviso)
    <div class="alert {{ $pagoAviso->ehDinheiro() ? 'alert-danger' : 'alert-warning' }} small mb-3">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong>Esta reserva já está paga (R$ {{ number_format($pagoAviso->amount, 2, ',', '.') }}).</strong>
        Ao cancelar, o cliente será <strong>reembolsado integralmente</strong> em
        <strong class="text-success">R$ {{ number_format($pagoAviso->amount, 2, ',', '.') }}</strong>
        — o valor sai do caixa como reembolso (fica pendente se o caixa estiver fechado).
        <div class="mt-2">
            <i class="bi bi-cash-coin me-1"></i> <strong>{{ $pagoAviso->comoReembolsar() }}</strong>
        </div>
        <div class="mt-2">Tem certeza que deseja cancelar mesmo assim?</div>
    </div>
@endif

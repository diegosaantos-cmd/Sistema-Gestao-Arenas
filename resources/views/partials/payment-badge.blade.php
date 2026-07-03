{{--
    Selo de situação de pagamento de uma reserva.
    Uso: @include('partials.payment-badge', ['booking' => $b])
--}}
@php
    $sit = $booking->situacaoPagamento();
    $mapPagamento = [
        'pago'     => ['Pago',      'bg-success'],
        'a_pagar'  => ['A pagar',   'bg-warning text-dark'],
        'atrasado' => ['Atrasado',  'bg-danger'],
    ];
@endphp
@if ($sit)
    <span class="badge {{ $mapPagamento[$sit][1] }}">{{ $mapPagamento[$sit][0] }}</span>
@endif
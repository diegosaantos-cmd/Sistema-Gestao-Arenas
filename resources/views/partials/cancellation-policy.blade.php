{{--
    Política de cancelamento/edição da arena.
    Uso: @include('partials.cancellation-policy', ['arena' => $arena])
    Mostrada tanto na página da arena quanto na tela de reservar.
--}}
<div class="dashboard-box mt-3">
    <h3 class="fw-bold mb-2" style="font-size: 1rem;">
        <i class="bi bi-shield-check me-1"></i> Cancelamento e edição
    </h3>

    @if (! $arena->charges_cancellation_fee)
        <p class="mb-0 small text-muted">
            Esta arena <strong>não cobra taxa</strong> de cancelamento.
            Você pode <strong>cancelar ou editar</strong> a reserva a qualquer
            momento antes do horário, <strong>sem custo</strong>.
        </p>
    @else
        @php
            $valorTaxa = $arena->cancellation_fee_type === 'percent'
                ? rtrim(rtrim(number_format($arena->cancellation_fee_value, 2, ',', '.'), '0'), ',') . '% do valor'
                : 'R$ ' . number_format($arena->cancellation_fee_value, 2, ',', '.');
        @endphp
        @if ($arena->cancellation_fee_mode === 'window')
            <p class="mb-1 small">
                <i class="bi bi-check-circle text-success me-1"></i>
                <strong>Grátis</strong> para cancelar e editar até
                <strong>{{ $arena->cancellation_fee_window_hours }}h antes</strong> do início.
            </p>
            <p class="mb-0 small text-danger">
                <i class="bi bi-exclamation-circle me-1"></i>
                A partir daí: cancelar tem taxa de <strong>{{ $valorTaxa }}</strong>
                e a edição fica bloqueada.
            </p>
        @else
            <p class="mb-0 small text-danger">
                <i class="bi bi-exclamation-circle me-1"></i>
                Cancelar uma reserva já confirmada tem taxa de
                <strong>{{ $valorTaxa }}</strong>. A edição só é possível
                enquanto a reserva está <strong>pendente</strong>.
            </p>
        @endif
    @endif
</div>

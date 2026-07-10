{{--
    Selo de origem da reserva: feita pelo cliente no site (online) ou registrada
    no balcão da arena (presencial).
    Uso: @include('partials.origin-badge', ['booking' => $b])
--}}
@if ($booking->ehPresencial())
    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle text-nowrap">
        <i class="bi bi-shop me-1"></i>Na arena
    </span>
@else
    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-nowrap">
        <i class="bi bi-globe me-1"></i>Online
    </span>
@endif

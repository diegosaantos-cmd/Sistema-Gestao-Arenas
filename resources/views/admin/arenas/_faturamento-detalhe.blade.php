{{-- Detalhe do faturamento REAL (via caixa). Recebe:
     fatMes, fatAcumulado, fatAno, fatMensal, anoFaturamento, anosFaturamento,
     formAction (rota do filtro por ano) e formHidden (campos ocultos do form). --}}

{{-- Resumo líquido: mês e acumulado --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="border rounded p-3 h-100">
            <span class="text-muted">Líquido no mês</span>
            <div class="fs-3 fw-bold {{ $fatMes['liquido'] < 0 ? 'text-danger' : 'text-success' }}">
                R$ {{ number_format($fatMes['liquido'], 2, ',', '.') }}
            </div>
            <small class="text-muted">Entradas − saídas do mês atual</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="border rounded p-3 h-100">
            <span class="text-muted">Líquido acumulado</span>
            <div class="fs-3 fw-bold {{ $fatAcumulado['liquido'] < 0 ? 'text-danger' : 'text-success' }}">
                R$ {{ number_format($fatAcumulado['liquido'], 2, ',', '.') }}
            </div>
            <small class="text-muted">Desde o início dos registros</small>
        </div>
    </div>
</div>

{{-- Filtro por ano + composição --}}
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h6 class="fw-bold mb-0">Composição do faturamento</h6>
    <form method="GET" action="{{ $formAction }}">
        @foreach ($formHidden ?? [] as $nome => $valor)
            <input type="hidden" name="{{ $nome }}" value="{{ $valor }}">
        @endforeach
        <select name="ano_faturamento" class="form-select form-select-sm"
                aria-label="Selecionar ano" onchange="this.form.submit()">
            @foreach ($anosFaturamento as $ano)
                <option value="{{ $ano }}" @selected($anoFaturamento === $ano)>{{ $ano }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="border rounded mb-4">
    <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
            <span><i class="bi bi-calendar-check text-secondary me-1"></i> Faturamento de reservas</span>
            <span class="fw-semibold text-success">+ R$ {{ number_format($fatAno['reservas'], 2, ',', '.') }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            <span><i class="bi bi-x-circle text-secondary me-1"></i> Taxas de cancelamento</span>
            <span class="fw-semibold text-success">+ R$ {{ number_format($fatAno['cancelamentos'], 2, ',', '.') }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            <span><i class="bi bi-plus-circle text-secondary me-1"></i> Entradas avulsas</span>
            <span class="fw-semibold text-success">+ R$ {{ number_format($fatAno['avulsas'], 2, ',', '.') }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            <span><i class="bi bi-dash-circle text-secondary me-1"></i> Despesas (saídas)</span>
            <span class="fw-semibold text-danger">− R$ {{ number_format($fatAno['despesas'], 2, ',', '.') }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between bg-light">
            <span class="fw-bold">Faturamento líquido em {{ $anoFaturamento }}</span>
            <span class="fw-bold {{ $fatAno['liquido'] < 0 ? 'text-danger' : 'text-success' }}">
                R$ {{ number_format($fatAno['liquido'], 2, ',', '.') }}
            </span>
        </li>
    </ul>
</div>

{{-- Líquido por mês --}}
<h6 class="fw-bold mb-2">Líquido por mês ({{ $anoFaturamento }})</h6>
<div class="table-responsive border rounded" style="max-height: 45vh; overflow-y: auto; padding-bottom: 6px;">
    <table class="table align-middle mb-0 admin-sticky-table">
        <thead class="table-light sticky-top">
            <tr>
                <th class="ps-3">Mês</th>
                <th class="text-end">Entradas</th>
                <th class="text-end">Saídas</th>
                <th class="text-end pe-3">Líquido</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($fatMensal as $registro)
                <tr>
                    <td class="ps-3">{{ \Carbon\Carbon::createFromFormat('Y-m', $registro->mes)->translatedFormat('F/Y') }}</td>
                    <td class="text-end text-success">R$ {{ number_format($registro->entradas, 2, ',', '.') }}</td>
                    <td class="text-end text-danger">R$ {{ number_format($registro->despesas, 2, ',', '.') }}</td>
                    <td class="text-end fw-bold pe-3 {{ $registro->liquido < 0 ? 'text-danger' : 'text-success' }}">
                        R$ {{ number_format($registro->liquido, 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum lançamento no caixa neste ano.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

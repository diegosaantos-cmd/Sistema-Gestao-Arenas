@forelse ($clientes as $cliente)
    <tr>
        <td class="ps-3 fw-bold">{{ $cliente->user?->name ?? '—' }}</td>
        <td class="text-break">{{ $cliente->user?->email ?? '—' }}</td>
        <td class="text-break">{{ $cliente->user?->phone ?: '—' }}</td>
        <td>
            {{ $cliente->date_of_birth
                ? \Carbon\Carbon::parse($cliente->date_of_birth)->format('d/m/Y')
                : '—' }}
        </td>
        <td>{{ $cliente->user?->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
        <td class="text-end fw-bold">{{ $cliente->reservas_total }}</td>
        <td class="text-end pe-3 fw-bold text-success">
            R$ {{ number_format($cliente->valor_total, 2, ',', '.') }}
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center text-muted py-4">
            Nenhum cliente encontrado.
        </td>
    </tr>
@endforelse

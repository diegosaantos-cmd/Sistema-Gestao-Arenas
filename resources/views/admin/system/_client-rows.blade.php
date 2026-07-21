@forelse ($usuarios as $usuario)
    <tr class="client-main-row">
        <td class="ps-3 fw-bold client-main-name">{{ $usuario->name }}</td>
        <td class="text-break client-main-email" style="overflow-wrap: anywhere;">{{ $usuario->email }}</td>
        <td class="text-break client-secondary-column">{{ $usuario->phone ?: '—' }}</td>
        <td class="text-nowrap client-secondary-column">
            {{ $usuario->client?->date_of_birth
                ? \Carbon\Carbon::parse($usuario->client->date_of_birth)->format('d/m/Y')
                : '—' }}
        </td>
        <td class="client-secondary-column">
            @if ($usuario->terms_accepted_at)
                <span class="badge bg-success">Aceitos</span>
                <div class="small text-muted text-nowrap">
                    <span class="d-block">{{ $usuario->terms_accepted_at->format('d/m/Y') }}</span>
                    <span class="d-block">{{ $usuario->terms_accepted_at->format('H:i') }}</span>
                </div>
            @else
                <span class="text-muted">Não registrado</span>
            @endif
        </td>
        <td class="text-nowrap client-secondary-column">
            @if ($usuario->created_at)
                <span class="d-block">{{ $usuario->created_at->format('d/m/Y') }}</span>
                <span class="d-block small text-muted">{{ $usuario->created_at->format('H:i') }}</span>
            @else
                —
            @endif
        </td>
        <td class="client-main-status">
            <span class="badge {{ $usuario->active ? 'bg-success' : 'bg-danger' }}">
                {{ $usuario->active ? 'Ativo' : 'Bloqueado' }}
            </span>
        </td>
        <td class="text-end pe-3 client-main-actions">
            {{-- Ações em linha (horizontal), como nas outras tabelas do admin. --}}
            <div class="d-flex flex-wrap justify-content-end gap-1 client-actions-wrap">
                <a href="{{ route('admin.users.show', $usuario) }}" class="btn btn-primary btn-sm">Detalhes</a>
                @include('admin.system._client-actions', [
                    'usuario' => $usuario,
                    'divida' => ($dividas ?? collect())->get($usuario->client?->id),
                ])
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center text-muted py-4">
            Nenhum cliente encontrado.
        </td>
    </tr>
@endforelse

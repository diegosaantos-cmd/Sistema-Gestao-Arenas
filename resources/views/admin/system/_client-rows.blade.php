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
            <div class="d-none d-md-block ms-auto" style="max-width: 115px;">
                @include('admin.system._client-actions', ['usuario' => $usuario])
            </div>
            <button type="button"
                    class="btn btn-outline-primary btn-sm d-md-none px-2 py-1 text-nowrap"
                    data-client-mobile-details>
                Ver mais
            </button>
        </td>
    </tr>

    <tr class="client-mobile-details-row d-none d-md-none">
        <td colspan="8" class="p-3 bg-light">
            <div class="row g-3 small">
                <div class="col-6">
                    <span class="fw-bold text-dark">Telefone</span><br>
                    <span class="text-break">{{ $usuario->phone ?: '—' }}</span>
                </div>
                <div class="col-6">
                    <span class="fw-bold text-dark">Nascimento</span><br>
                    <span>
                        {{ $usuario->client?->date_of_birth
                            ? \Carbon\Carbon::parse($usuario->client->date_of_birth)->format('d/m/Y')
                            : '—' }}
                    </span>
                </div>
                <div class="col-6">
                    <span class="fw-bold text-dark">Termos</span><br>
                    @if ($usuario->terms_accepted_at)
                        <span>Aceitos</span>
                        <div class="text-muted">
                            {{ $usuario->terms_accepted_at->format('d/m/Y') }}<br>
                            {{ $usuario->terms_accepted_at->format('H:i') }}
                        </div>
                    @else
                        <span class="text-muted">Não registrado</span>
                    @endif
                </div>
                <div class="col-6">
                    <span class="fw-bold text-dark">Cadastro</span><br>
                    @if ($usuario->created_at)
                        <span>{{ $usuario->created_at->format('d/m/Y') }}</span><br>
                        <span class="text-muted">{{ $usuario->created_at->format('H:i') }}</span>
                    @else
                        —
                    @endif
                </div>
                <div class="col-12">
                    @include('admin.system._client-actions', ['usuario' => $usuario])
                </div>
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

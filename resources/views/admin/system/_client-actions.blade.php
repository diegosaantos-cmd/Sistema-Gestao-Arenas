<div class="d-flex flex-wrap gap-1 client-system-actions">
    @if ($usuario->active)
        <form method="POST" action="{{ route('admin.users.block', $usuario) }}">
            @csrf
            @method('PATCH')
            <button type="button" class="btn btn-warning btn-sm"
                    data-confirm
                    data-confirm-title="Bloquear cliente"
                    data-confirm-message="Deseja realmente bloquear {{ $usuario->name }}? Ele perderá o acesso ao sistema."
                    data-confirm-label="Sim, bloquear"
                    data-confirm-variant="warning">Bloquear</button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.users.unblock', $usuario) }}">
            @csrf
            @method('PATCH')
            <button type="button" class="btn btn-success btn-sm"
                    data-confirm
                    data-confirm-title="Desbloquear cliente"
                    data-confirm-message="Deseja desbloquear {{ $usuario->name }}? Ele voltará a ter acesso ao sistema."
                    data-confirm-label="Sim, desbloquear"
                    data-confirm-variant="success">Desbloquear</button>
        </form>
    @endif

    <form method="POST" action="{{ route('admin.users.destroy', $usuario) }}">
        @csrf
        @method('DELETE')
        <button type="button" class="btn btn-danger btn-sm"
                data-confirm
                data-confirm-title="Excluir cliente"
                data-confirm-message="Deseja realmente excluir {{ $usuario->name }}? O histórico será preservado."
                data-confirm-label="Sim, excluir"
                data-confirm-variant="danger">Excluir</button>
    </form>
</div>

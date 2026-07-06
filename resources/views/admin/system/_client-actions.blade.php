<div class="d-grid gap-2 client-system-actions">
    @if ($usuario->active)
        <form method="POST" action="{{ route('admin.users.block', $usuario) }}"
              onsubmit="return confirm('Deseja realmente bloquear este cliente? Ele perderá o acesso ao sistema.')">
            @csrf
            @method('PATCH')
            <button class="btn btn-outline-warning btn-sm w-100">Bloquear</button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.users.unblock', $usuario) }}"
              onsubmit="return confirm('Deseja desbloquear este cliente?')">
            @csrf
            @method('PATCH')
            <button class="btn btn-outline-success btn-sm w-100">Desbloquear</button>
        </form>
    @endif

    <form method="POST" action="{{ route('admin.users.destroy', $usuario) }}"
          onsubmit="return confirm('Deseja realmente excluir este cliente? O histórico será preservado.')">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger btn-sm w-100">Excluir</button>
    </form>
</div>

<div class="dropdown">
    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button"
            data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-arrow-left-right me-1"></i> Trocar de empresa
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow">
        @foreach ($empresas as $empresa)
            <li>
                <a class="dropdown-item {{ $empresa->id === $owner->id ? 'active' : '' }}"
                   href="{{ route('admin.owners.show', $empresa) }}">
                    {{ $empresa->company_name }}
                </a>
            </li>
        @endforeach
    </ul>
</div>

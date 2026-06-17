@extends('layouts.main')

@section('title', 'Painel do Funcionário')

@section('content')

<div class="container py-4">

```
<div class="mb-4">
    <h1 class="fw-bold">
        Painel do Funcionário
    </h1>

    <p class="text-muted fs-4">
        Gerencie agendamentos e atendimento ao cliente
    </p>
</div>

<!-- Cards -->
<div class="row g-4 mb-4">

    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <h4 class="text-secondary">
                        Agendamentos Hoje
                    </h4>

                    <h1 class="fw-bold">
                        #
                    </h1>
                </div>

                <div class="fs-1 text-secondary">
                    📅
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <h4 class="text-secondary">
                        Pendentes
                    </h4>

                    <h1 class="fw-bold">
                        #
                    </h1>
                </div>

                <div class="fs-1 text-warning">
                    •••
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <h4 class="text-secondary">
                        Confirmados
                    </h4>

                    <h1 class="fw-bold">
                        #
                    </h1>
                </div>

                <div class="fs-1 text-success">
                    ✔
                </div>

            </div>
        </div>
    </div>

</div>

<!-- Tabs -->
<div class="card shadow-sm border-0 mb-4">

    <div class="card-body p-0">

        <ul class="nav nav-tabs px-3 pt-3" id="agendamentoTabs">

            <li class="nav-item">
                <button class="nav-link active"
                        data-bs-toggle="tab"
                        data-bs-target="#hoje">
                    HOJE (#)
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#semana">
                    ESTA SEMANA (#)
                </button>
            </li>

        </ul>

    </div>

</div>

<!-- Tabela -->
<div class="card shadow-sm border-0">

    <div class="tab-content">

        <div class="tab-pane fade show active" id="hoje">

            <table class="table table-hover align-middle mb-0">

                <thead>
                    <tr>
                        <th>Horário</th>
                        <th>Cliente</th>
                        <th>Arena</th>
                        <th>Quadra</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>

                    
 class="btn btn-sm btn-outline-danger">
                                    Cancelar
                                </button>

                            </td>

                        </tr>

                    

                </tbody>

            </table>

        </div>

    </div>

</div>
```

</div>

@endsection

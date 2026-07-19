{{--
    Erros SÓ desta etapa do assistente.

    Antes havia um único bloco fora das etapas: o erro de senha (etapa 1) seguia
    visível na etapa 3, e só sumia recarregando a página — o que limpava o
    formulário inteiro. Aqui cada etapa mostra apenas o que é dela.

    Recebe $campos (array com os nomes dos campos da etapa). Campos de array
    (horarios, pagamentos, quadras) entram pelo prefixo, então "quadras.0.nome"
    aparece na etapa das quadras.
--}}
@php
    $errosDaEtapa = collect($errors->keys())
        ->filter(fn ($campo) => in_array(explode('.', $campo)[0], $campos, true))
        ->flatMap(fn ($campo) => $errors->get($campo));
@endphp

@if ($errosDaEtapa->isNotEmpty())
    <div class="alert alert-danger">
        <strong>Corrija para continuar:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errosDaEtapa as $erro)
                <li>{{ $erro }}</li>
            @endforeach
        </ul>
    </div>
@endif

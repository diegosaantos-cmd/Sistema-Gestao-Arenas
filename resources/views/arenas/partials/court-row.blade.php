{{--
    Uma quadra (card) dentro do formulário.

    Recebe:
      $index  - índice no array quadras[] (número, ou '__INDEX__' no template do JS)
      $sports - lista de esportes (value => label)
      $q      - dados antigos dessa quadra (old()), ou [] quando nova

    Markup compartilhado pela renderização inicial e pelo <template> que o JS clona.
--}}
@php
    $q = $q ?? [];
    // Quadra nova começa ativa; numa repopulação de erro, respeita o que veio.
    $ativaChecked = empty($q) ? true : ! empty($q['ativa']);
    $esportesMarcados = $q['esportes'] ?? [];
    // "Multiesporte" começa marcado se todos os esportes estiverem selecionados.
    $todosMarcados = count(array_intersect(array_keys($sports), $esportesMarcados)) === count($sports);
@endphp

<div class="ct__card" data-court-row>

    <div class="ct__head">
        <strong data-court-num>Quadra</strong>
        <button type="button"
                class="ct__remove"
                data-remove-court
                aria-label="Remover quadra">&times;</button>
    </div>

    <div class="ct__fields">
        <input type="text"
               class="ct__input ct__input--name"
               name="quadras[{{ $index }}][nome]"
               placeholder="Nome (ex.: Quadra 1)"
               value="{{ $q['nome'] ?? '' }}"
               required>

        {{-- Texto (e não number) porque a máscara insere ponto de milhar e vírgula,
             que um input[type=number] rejeita. O servidor normaliza o que chega. --}}
        <input type="text"
               inputmode="decimal"
               class="ct__input ct__input--price"
               name="quadras[{{ $index }}][valor_hora]"
               placeholder="Valor/hora (R$)"
               data-mask="moeda"
               value="{{ $q['valor_hora'] ?? '' }}"
               required>

        <label class="ct__active">
            <input type="checkbox"
                   name="quadras[{{ $index }}][ativa]"
                   value="1"
                   {{ $ativaChecked ? 'checked' : '' }}>
            Ativa
        </label>
    </div>

    <textarea class="ct__input ct__textarea"
              name="quadras[{{ $index }}][descricao]"
              rows="2"
              placeholder="Descrição da quadra (piso, cobertura, iluminação...)">{{ $q['descricao'] ?? '' }}</textarea>

    <div class="ct__sports">
        <span class="ct__sports-label">Esportes praticados:</span>

        <label class="ct__sport ct__sport--all">
            <input type="checkbox"
                   data-court-all-sports
                   {{ $todosMarcados ? 'checked' : '' }}>
            <span>Multiesporte (todos)</span>
        </label>

        @foreach ($sports as $value => $label)
            <label class="ct__sport">
                <input type="checkbox"
                       name="quadras[{{ $index }}][esportes][]"
                       value="{{ $value }}"
                       {{ in_array($value, $esportesMarcados) ? 'checked' : '' }}>
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>

</div>

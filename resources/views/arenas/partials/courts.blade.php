{{--
    Quadras da arena (uma ou várias).

    Reutilizado pelos formulários de "Nova Arena" e "Cadastro de Proprietário".
    Comportamento (adicionar/remover quadras) em public/js/courts.js
    e estilo em public/css/courts.css.

    Envia quadras[N][nome|valor_hora|ativa|esportes[]] -> salvos em courts + court_sports.
    A linha de cada quadra fica no partial court-row (compartilhado com o template do JS).
--}}

@once
    <link rel="stylesheet" href="/css/courts.css">
@endonce

@php
    $sports = \App\Models\Court::SPORTS;
    $quadrasOld = old('quadras');
@endphp

<div class="ct" data-courts>

    <h5 class="ct__title">Quadras</h5>

    <p class="ct__hint">
        Cadastre <strong>ao menos uma</strong> quadra. Marque vários esportes
        para uma quadra poliesportiva.
    </p>

    <div class="ct__list" data-courts-list>
        @if ($quadrasOld)
            @foreach ($quadrasOld as $i => $q)
                @include('arenas.partials.court-row', ['index' => $i, 'sports' => $sports, 'q' => $q])
            @endforeach
        @else
            @include('arenas.partials.court-row', ['index' => 0, 'sports' => $sports, 'q' => []])
        @endif
    </div>

    <button type="button" class="ct__add" data-add-court>
        + Adicionar quadra
    </button>

    @error('quadras')
        <p class="ct__error">{{ $message }}</p>
    @enderror

    {{-- Modelo clonado pelo JS ao adicionar uma quadra --}}
    <template data-court-template>
        @include('arenas.partials.court-row', ['index' => '__INDEX__', 'sports' => $sports, 'q' => []])
    </template>

</div>

@once
    <script src="/js/masks.js" defer></script>
    <script src="/js/courts.js" defer></script>
@endonce

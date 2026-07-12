@props(['href', 'class' => 'mb-3'])

{{--
    Botão "Voltar" padrão de topo de página.
    Uso: <x-back :href="route('...')" />
         <x-back :href="..." class="mb-0" />   (quando fica ao lado de outro botão)

    Posição, estilo e texto ficam SÓ aqui — todas as telas herdam o mesmo.
    O texto é sempre "← Voltar" (não nomeia o destino, para nunca ficar
    incoerente com para onde realmente leva). É um botão pequeno, então cabe
    bem tanto no celular quanto em telas grandes.
--}}
<a href="{{ $href }}" class="btn btn-dark btn-sm {{ $class }}">
    <i class="bi bi-arrow-left me-1"></i> Voltar
</a>

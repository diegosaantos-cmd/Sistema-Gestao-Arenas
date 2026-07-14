@props(['href', 'class' => 'mb-3', 'history' => false])

{{--
    Botão "Voltar" padrão de topo de página.
    Uso: <x-back :href="route('...')" />
         <x-back :href="..." class="mb-0" />   (quando fica ao lado de outro botão)
         <x-back :href="..." history />         (volta pela pilha do navegador)

    Posição, estilo e texto ficam SÓ aqui — todas as telas herdam o mesmo.
    O texto é sempre "← Voltar" (não nomeia o destino, para nunca ficar
    incoerente com para onde realmente leva). É um botão pequeno, então cabe
    bem tanto no celular quanto em telas grandes.

    history=true: o clique usa o histórico do navegador (volta para a página de
    onde você veio, seja ela qual for) em vez de um destino fixo. Evita "loop de
    Voltar" entre duas telas que se linkam (ex.: reserva ⇄ lançamento do caixa).
    O href continua como fallback (aberto direto, sem histórico, ou sem JS).
--}}
<a href="{{ $href }}" class="btn btn-dark btn-sm {{ $class }}"
   @if ($history) onclick="if (window.history.length > 1) { window.history.back(); return false; }" @endif>
    <i class="bi bi-arrow-left me-1"></i> Voltar
</a>

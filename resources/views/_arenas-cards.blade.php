{{--
    Uma página da vitrine de arenas: os cards e, no fim, o botão que leva à
    página seguinte.

    Vive separado porque é usado em dois lugares: a home renderiza a primeira
    página com ele, e o scroll infinito pede as seguintes na rota `/` com
    `?parcial=1`, que devolve só este pedaço para a tela anexar ao que já está
    na lista.

    O botão vem junto de propósito. É ele que carrega o endereço da próxima
    página, então cada pedaço que chega já traz o "para onde ir depois" — e
    quando não há mais páginas, simplesmente não vem botão nenhum, que é como o
    scroll infinito sabe parar.
--}}
@foreach ($arenas as $arena)

    <div class="col-6 col-lg-3 mb-4">
        @include('client.arenas._gallery-card', [
            'favoritasIds' => $favoritasIds ?? [],
            'arenaUrl' => route('client.arenas.show', [$arena, 'origem' => 'inicio']),
            'botaoTexto' => 'Ver arena',
        ])
    </div>

@endforeach

@if ($arenas->hasMorePages())
    {{-- Sem JavaScript isto continua sendo um link comum para a página
         seguinte: a lista vira paginação normal em vez de quebrar. --}}
    <div class="col-12 text-center mb-4" data-arena-mais>
        <a href="{{ $arenas->nextPageUrl() }}" class="btn btn-outline-primary px-4">
            <span data-arena-mais-texto>
                <i class="bi bi-arrow-down-circle me-1"></i> Ver mais arenas
            </span>
        </a>
    </div>
@endif

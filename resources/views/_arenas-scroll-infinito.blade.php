{{--
    Rolagem da vitrine de arenas: a caixa que rola e o carregamento das páginas
    seguintes sem sair da tela.

    Carrega em cima do botão "ver mais" que vem em `_arenas-cards`: pede a
    página que está no href dele com `?parcial=1`, troca o botão pelo pedaço que
    chegou (cards + o botão da página seguinte) e repete. Quando o servidor não
    manda mais botão, acabou — não existe contador de páginas aqui, quem decide
    é sempre o servidor.

    Sem JavaScript nada disso roda e o botão segue sendo um link normal para a
    página seguinte.
--}}
@once
    <style>
        /* A lista rola dentro da própria caixa para o fim da página ficar
           sempre no mesmo lugar, em vez de recuar a cada carga.

           Só a partir de lg: no celular, rolagem dentro de rolagem prende o
           dedo na caixa e a pessoa não consegue sair da seção. Lá a lista flui
           na página e cresce apenas quando se toca em "ver mais". */
        @media (min-width: 992px) {
            .arena-vitrine {
                max-height: 78vh;
                overflow-y: auto;
                /* Ao chegar ao fim da caixa a rolagem para, em vez de
                   continuar arrastando a página junto. */
                overscroll-behavior: contain;
                /* Respiro para a barra de rolagem não encostar nos cards. */
                padding-right: .75rem;
            }

            /* A caixa recebe foco pelo teclado (tabindex), então precisa
               mostrar quando está com ele — é assim que se rola sem mouse. */
            .arena-vitrine:focus-visible {
                outline: 2px solid var(--bs-primary);
                outline-offset: 4px;
                border-radius: .5rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lista = document.querySelector('[data-arena-results]');
            const caixa = document.querySelector('[data-arena-scroll]');

            if (! lista) {
                return;
            }

            let carregando = false;
            let vigia = null;

            const botao = () => lista.querySelector('[data-arena-mais]');

            // Quem rola é a caixa (desktop) ou ninguém (celular). Ler do estilo
            // calculado em vez de repetir a largura do @media aqui: a regra do
            // ponto de virada mora só no CSS.
            const caixaRola = () =>
                caixa && getComputedStyle(caixa).overflowY === 'auto';

            async function carregarMais() {
                const bloco = botao();

                if (carregando || ! bloco) {
                    return;
                }

                const link = bloco.querySelector('a');
                const texto = bloco.querySelector('[data-arena-mais-texto]');
                const original = texto.innerHTML;

                carregando = true;
                link.classList.add('disabled');
                texto.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span> Carregando…';

                try {
                    // `searchParams.set` em vez de concatenar: se o endereço já
                    // trouxer `parcial`, sobrescreve em vez de duplicar.
                    const url = new URL(link.href, window.location.origin);
                    url.searchParams.set('parcial', '1');

                    const resposta = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (! resposta.ok) {
                        throw new Error('resposta ' + resposta.status);
                    }

                    const html = await resposta.text();

                    // O pedaço traz de carona o <script> do lightbox. Ele não
                    // roda quando entra por innerHTML, e nem precisa: favoritos,
                    // lightbox e carrossel escutam no document, então já valem
                    // para os cards novos. Tiro para não acumular script morto
                    // na página a cada carga.
                    const pedaco = document.createElement('div');
                    pedaco.innerHTML = html;
                    pedaco.querySelectorAll('script, style').forEach(function (morto) {
                        morto.remove();
                    });

                    // Tira o botão antigo antes de anexar: o pedaço que chegou
                    // já traz o botão da próxima página no fim.
                    bloco.remove();
                    lista.append(...pedaco.children);

                    carregando = false;
                    observar();
                } catch (erro) {
                    // Deixa o botão utilizável para tentar de novo, em vez de a
                    // lista morrer em silêncio.
                    carregando = false;
                    link.classList.remove('disabled');
                    texto.innerHTML =
                        '<i class="bi bi-arrow-clockwise me-1"></i> Não deu para carregar. Tentar de novo';
                }
            }

            function observar() {
                const bloco = botao();

                if (vigia && bloco) {
                    vigia.observe(bloco);
                }
            }

            // Dispara sozinho quando o botão se aproxima do fim da caixa. A
            // margem começa a carregar um pouco antes de ele aparecer, para a
            // rolagem não dar um solavanco esperando.
            function montarVigia() {
                if (vigia) {
                    vigia.disconnect();
                    vigia = null;
                }

                // Celular: sem carregamento automático. A página só cresce
                // quando a pessoa toca no botão, e cresce de forma limitada.
                if (! caixaRola()) {
                    return;
                }

                vigia = new IntersectionObserver(function (entradas) {
                    entradas.forEach(function (entrada) {
                        if (entrada.isIntersecting) {
                            carregarMais();
                        }
                    });
                }, { root: caixa, rootMargin: '300px' });

                observar();
            }

            // Clique continua funcionando sempre: é o caminho de quem usa
            // teclado, o único no celular, e a saída quando algo falhou.
            lista.addEventListener('click', function (evento) {
                const link = evento.target.closest('[data-arena-mais] a');

                if (link) {
                    evento.preventDefault();
                    carregarMais();
                }
            });

            // Virar o celular (ou redimensionar a janela) cruza o ponto do
            // @media e troca quem rola, então o vigia é remontado.
            let aguardando;
            window.addEventListener('resize', function () {
                clearTimeout(aguardando);
                aguardando = setTimeout(montarVigia, 200);
            });

            montarVigia();
        });
    </script>
@endonce

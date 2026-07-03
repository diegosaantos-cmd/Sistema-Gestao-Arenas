@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.arena-search-form');
            const campo = form?.querySelector('input[name="busca"]');
            const resultados = document.querySelector('[data-arena-results]');

            if (!form || !campo || !resultados) return;

            const conteudoInicial = campo.value.trim() === '' ? resultados.innerHTML : null;
            let temporizador;
            let requisicao;

            async function pesquisar() {
                const termo = campo.value.trim();

                if (termo === '' && conteudoInicial !== null) {
                    requisicao?.abort();
                    resultados.innerHTML = conteudoInicial;

                    if (form.dataset.updateUrl === 'true') {
                        history.replaceState({}, '', form.action);
                    }

                    return;
                }

                requisicao?.abort();
                requisicao = new AbortController();

                const url = new URL(form.action, window.location.origin);

                if (termo !== '') {
                    url.searchParams.set('busca', termo);
                }

                resultados.style.opacity = '0.55';

                try {
                    const resposta = await fetch(url, {
                        headers: {'X-Requested-With': 'XMLHttpRequest'},
                        signal: requisicao.signal,
                    });

                    if (!resposta.ok) throw new Error('Falha na pesquisa');

                    const pagina = new DOMParser().parseFromString(await resposta.text(), 'text/html');
                    const novosResultados = pagina.querySelector('[data-arena-results]');

                    if (novosResultados) {
                        resultados.innerHTML = novosResultados.innerHTML;
                    }

                    if (form.dataset.updateUrl === 'true') {
                        history.replaceState({}, '', url);
                    }
                } catch (erro) {
                    if (erro.name !== 'AbortError') {
                        form.submit();
                    }
                } finally {
                    resultados.style.opacity = '';
                }
            }

            campo.addEventListener('input', function () {
                clearTimeout(temporizador);
                temporizador = setTimeout(pesquisar, 250);
            });

            form.addEventListener('submit', function (evento) {
                evento.preventDefault();
                clearTimeout(temporizador);
                pesquisar();
            });
        });
    </script>
@endonce

{{-- Controla os carrosséis horizontais (fotos, quadras): as setas deslizam
     um item por clique; e escondem-se quando tudo já cabe na tela. --}}
<script>
    (function () {
        function passo(track) {
            const item = track.querySelector(':scope > *');
            const estilo = getComputedStyle(track);
            const gap = parseFloat(estilo.columnGap || estilo.gap) || 0;
            return item ? item.getBoundingClientRect().width + gap : track.clientWidth;
        }

        // Clique nas setas: rola um item para o lado.
        document.addEventListener('click', function (evento) {
            const botao = evento.target.closest('[data-scroll]');
            if (!botao) return;

            const track = document.getElementById(botao.dataset.scroll);
            if (!track) return;

            const dir = botao.dataset.scrollDir === 'prev' ? -1 : 1;
            track.scrollBy({ left: dir * passo(track), behavior: 'smooth' });
        });

        // Some com as setas quando não há o que rolar (tudo já visível). Funciona
        // esteja a seta sobreposta (fotos) ou no cabeçalho (quadras): acha o track
        // pelo próprio data-scroll.
        function ajustarSetas() {
            document.querySelectorAll('[data-scroll]').forEach(function (botao) {
                const track = document.getElementById(botao.dataset.scroll);
                if (!track) return;

                const rola = track.scrollWidth > track.clientWidth + 2;
                botao.classList.toggle('d-none', !rola);
            });
        }

        document.addEventListener('DOMContentLoaded', ajustarSetas);
        window.addEventListener('load', ajustarSetas);
        window.addEventListener('resize', ajustarSetas);
    })();
</script>

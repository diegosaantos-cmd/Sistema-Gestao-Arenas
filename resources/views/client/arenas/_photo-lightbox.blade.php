{{-- Lightbox compartilhado: um único modal por página. Qualquer botão com
     data-lightbox (JSON de URLs) o abre em tela cheia. Incluído via @once. --}}
<div class="modal fade" id="fotoLightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-black">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="fotoLightboxTitulo"></h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body d-flex align-items-center justify-content-center p-0 overflow-hidden">
                <div id="fotoLightboxCarousel" class="carousel slide w-100 h-100">
                    <div class="carousel-inner h-100" id="fotoLightboxInner"></div>
                    <button class="carousel-control-prev" type="button" id="fotoLightboxPrev"
                            data-bs-target="#fotoLightboxCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" id="fotoLightboxNext"
                            data-bs-target="#fotoLightboxCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Próxima</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', function (evento) {
        const botao = evento.target.closest('[data-lightbox]');
        if (!botao) return;

        let urls;
        try { urls = JSON.parse(botao.dataset.lightbox); } catch (_) { return; }
        if (!Array.isArray(urls) || urls.length === 0) return;

        const titulo = botao.dataset.lightboxTitulo || '';
        document.getElementById('fotoLightboxTitulo').textContent = titulo;

        // Abre já na foto clicada (índice); 0 se não informado / fora do intervalo.
        let inicio = parseInt(botao.dataset.lightboxIndex || '0', 10);
        if (isNaN(inicio) || inicio < 0 || inicio >= urls.length) inicio = 0;

        // Monta os slides via DOM (nada de innerHTML com dados) para evitar injeção.
        const inner = document.getElementById('fotoLightboxInner');
        inner.innerHTML = '';
        urls.forEach(function (url, i) {
            const item = document.createElement('div');
            item.className = 'carousel-item h-100' + (i === inicio ? ' active' : '');

            const wrap = document.createElement('div');
            wrap.className = 'd-flex align-items-center justify-content-center h-100 p-3';

            const img = document.createElement('img');
            img.src = url;
            img.alt = titulo;
            img.style.maxWidth = '100%';
            img.style.maxHeight = '100%';
            img.style.objectFit = 'contain';

            wrap.appendChild(img);
            item.appendChild(wrap);
            inner.appendChild(item);
        });

        // Uma foto só: esconde as setas.
        const soUma = urls.length < 2;
        document.getElementById('fotoLightboxPrev').classList.toggle('d-none', soUma);
        document.getElementById('fotoLightboxNext').classList.toggle('d-none', soUma);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('fotoLightbox')).show();
    });
</script>

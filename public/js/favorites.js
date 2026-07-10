/*
 * Favoritar / desfavoritar arena sem recarregar a página.
 *
 * Progressive enhancement: os botões continuam sendo <form> POST normais. Aqui
 * interceptamos o envio, chamamos a rota por fetch e trocamos só o coração no
 * lugar — a página não recarrega nem sobe ao topo. Se o fetch falhar (ou o JS
 * não rodar), o formulário é enviado do jeito tradicional.
 *
 * Marcação esperada:
 *   <form data-favorite-form [data-favorite-remove]>  (remove = tela de favoritas)
 *     <button data-fav-btn data-fav-style="card|button"> ...
 */
(function () {
    'use strict';

    document.addEventListener('submit', function (evento) {
        var form = evento.target.closest('[data-favorite-form]');
        if (!form) return;

        evento.preventDefault();

        var btn = form.querySelector('[data-fav-btn]');
        var token = form.querySelector('input[name="_token"]');
        if (!token) { form.submit(); return; }

        if (btn) btn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token.value,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
            .then(function (dados) { aplicar(form, btn, !!dados.favorited); })
            .catch(function () {
                // Não conseguiu por AJAX: faz o caminho normal (recarrega).
                form.submit();
            })
            .finally(function () { if (btn) btn.disabled = false; });
    });

    function aplicar(form, btn, favorito) {
        // Tela de favoritas: ao desfavoritar, some com o card em vez de deixar
        // um coração vazio numa página de "favoritas".
        if (! favorito && form.hasAttribute('data-favorite-remove')) {
            var card = form.closest('[data-favorite-card]');
            if (card) { card.remove(); return; }
        }

        if (! btn) return;
        var icone = btn.querySelector('i');
        var estilo = btn.getAttribute('data-fav-style');

        if (estilo === 'button') {
            // Botão com texto (página da arena).
            btn.classList.toggle('btn-danger', favorito);
            btn.classList.toggle('btn-outline-danger', ! favorito);
            if (icone) icone.className = 'bi me-1 ' + (favorito ? 'bi-heart-fill' : 'bi-heart');
            var label = btn.querySelector('[data-fav-label]');
            if (label) label.textContent = favorito ? 'Nas favoritas' : 'Favoritar';
        } else {
            // Coração no card.
            if (icone) icone.className = 'bi ' + (favorito ? 'bi-heart-fill text-danger' : 'bi-heart text-secondary');
            var titulo = favorito ? 'Remover das favoritas' : 'Adicionar às favoritas';
            btn.title = titulo;
            btn.setAttribute('aria-label', titulo);
        }
    }
})();

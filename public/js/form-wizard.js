/*
 * Formulário dividido em etapas.
 *
 * Marcação esperada:
 *   <form data-wizard novalidate>
 *     <div data-etapa data-campos="name,email">...</div>
 *     <button data-avancar> / <button data-voltar>
 *
 * O `novalidate` no form é proposital: sem ele, o navegador tentaria validar
 * campos das etapas escondidas ao enviar e travaria o envio sem mostrar erro
 * (não dá para focar um campo com display:none). A validação é feita etapa a
 * etapa, e o servidor continua sendo a autoridade final.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('[data-wizard]');
        if (!form) return;

        var etapas = Array.prototype.slice.call(form.querySelectorAll('[data-etapa]'));
        var indicadores = Array.prototype.slice.call(document.querySelectorAll('[data-indicador]'));
        if (!etapas.length) return;

        var atual = 0;

        function mostrar(indice) {
            atual = Math.max(0, Math.min(indice, etapas.length - 1));

            etapas.forEach(function (etapa, i) {
                etapa.classList.toggle('d-none', i !== atual);
            });

            indicadores.forEach(function (ind, i) {
                ind.classList.toggle('wz-passo--ativo', i === atual);
                ind.classList.toggle('wz-passo--feito', i < atual);
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function limparAviso(etapa) {
            var caixa = etapa.querySelector('[data-erro-etapa]');
            if (caixa) caixa.remove();
        }

        function mostrarAviso(etapa, mensagem) {
            limparAviso(etapa);

            var caixa = document.createElement('div');
            caixa.className = 'alert alert-warning';
            caixa.setAttribute('data-erro-etapa', '');
            caixa.textContent = mensagem;

            etapa.insertBefore(caixa, etapa.firstChild);
            caixa.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        /*
         * Valida a etapa visível: primeiro os campos nativos (required, type,
         * minlength...), depois uma validação própria da página, se houver.
         *
         * A validação própria existe porque partials como horários e formas de
         * pagamento não usam `required` — nenhuma regra nativa impediria avançar
         * com a etapa vazia.
         */
        function etapaValida(indice) {
            var etapa = etapas[indice];
            var campos = etapa.querySelectorAll('input, select, textarea');

            for (var i = 0; i < campos.length; i++) {
                var campo = campos[i];
                if (campo.disabled || campo.type === 'hidden') continue;

                if (!campo.checkValidity()) {
                    campo.reportValidity();
                    return false;
                }
            }

            var propria = (window.__validacoesEtapa || {})[indice];

            if (typeof propria === 'function') {
                var mensagem = propria(etapa);

                if (mensagem) {
                    mostrarAviso(etapa, mensagem);
                    return false;
                }
            }

            limparAviso(etapa);
            return true;
        }

        form.addEventListener('click', function (evento) {
            if (evento.target.closest('[data-avancar]')) {
                evento.preventDefault();
                if (etapaValida(atual)) mostrar(atual + 1);
                return;
            }

            if (evento.target.closest('[data-voltar]')) {
                evento.preventDefault();
                mostrar(atual - 1);
            }
        });

        /*
         * O form é `novalidate`, então o navegador não checa nada ao enviar.
         * Valida a última etapa aqui para não gastar uma ida ao servidor por
         * um esporte esquecido ou os termos não aceitos.
         */
        form.addEventListener('submit', function (evento) {
            if (!etapaValida(atual)) evento.preventDefault();
        });

        /*
         * Se o servidor devolveu erros, abre a PRIMEIRA etapa que contém um deles.
         * Sem isso, a pessoa cairia na etapa 1 sem entender onde está o problema.
         */
        var comErro = window.__camposComErro || [];

        if (comErro.length) {
            var indice = etapas.findIndex(function (etapa) {
                var campos = (etapa.getAttribute('data-campos') || '').split(',');

                return campos.some(function (campo) {
                    return comErro.some(function (erro) {
                        return erro === campo || erro.indexOf(campo + '.') === 0;
                    });
                });
            });

            mostrar(indice >= 0 ? indice : 0);
        } else {
            mostrar(0);
        }
    });
})();

/*
 * Máscaras de digitação: telefone, CPF/CNPJ, moeda e percentual.
 *
 * Uso: <input data-mask="telefone">   (11) 91234-5678
 *      <input data-mask="cpfcnpj">    000.000.000-00  ou  00.000.000/0000-00
 *      <input data-mask="moeda">      1.234,56        (formata ao sair do campo)
 *      <input data-mask="percentual"> 30  ou  30,5    (formata ao sair do campo)
 *
 * É apenas conveniência visual. O servidor continua validando e normalizando
 * o que chega — nunca confiar nesta máscara.
 *
 * Moeda e percentual só formatam no `focusout` ("após a digitação"): formatar a
 * cada tecla faria o cursor pular de posição enquanto a pessoa digita.
 */
(function () {
    'use strict';

    // O arquivo pode ser incluído por mais de um partial na mesma página.
    if (window.__mascarasCarregadas) return;
    window.__mascarasCarregadas = true;

    function digitos(valor) {
        return String(valor || '').replace(/\D/g, '');
    }

    /*
     * Lê um número escrito por humano. Se houver vírgula, ela é o separador
     * decimal (padrão brasileiro) e os pontos são milhar. Sem vírgula, o ponto
     * é tratado como separador decimal — é o formato que o servidor devolve
     * ("20.00") ao repopular o formulário depois de um erro.
     */
    function paraNumero(texto) {
        var limpo = String(texto || '').replace(/[^\d.,]/g, '');
        if (!limpo) return null;

        if (limpo.indexOf(',') >= 0) {
            limpo = limpo.replace(/\./g, '').replace(',', '.');
        }

        var numero = parseFloat(limpo);
        return isNaN(numero) ? null : numero;
    }

    /* 1234.5 -> "1.234,50" */
    function formatarMoeda(n) {
        return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /* 30 -> "30" | 30.5 -> "30,5" (sem separador de milhar: porcentagem não passa de 100) */
    function formatarPercentual(n) {
        return n.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 2, useGrouping: false });
    }

    /* (00) 0000-0000  ou  (00) 00000-0000 */
    function formatarTelefone(d) {
        if (!d) return '';

        var out = '(' + d.slice(0, 2);
        if (d.length <= 2) return out;

        var celular = d.length > 10;
        var meio = celular ? d.slice(2, 7) : d.slice(2, 6);
        out += ') ' + meio;

        var fim = celular ? d.slice(7, 11) : d.slice(6, 10);
        if (fim) out += '-' + fim;

        return out;
    }

    /* 000.000.000-00 */
    function formatarCpf(d) {
        var out = d.slice(0, 3);
        if (d.length > 3) out += '.' + d.slice(3, 6);
        if (d.length > 6) out += '.' + d.slice(6, 9);
        if (d.length > 9) out += '-' + d.slice(9, 11);
        return out;
    }

    /* 00.000.000/0000-00 */
    function formatarCnpj(d) {
        var out = d.slice(0, 2);
        if (d.length > 2) out += '.' + d.slice(2, 5);
        if (d.length > 5) out += '.' + d.slice(5, 8);
        if (d.length > 8) out += '/' + d.slice(8, 12);
        if (d.length > 12) out += '-' + d.slice(12, 14);
        return out;
    }

    /* Alterna sozinho entre CPF (11) e CNPJ (14) conforme a quantidade digitada. */
    function formatarCpfCnpj(d) {
        return d.length <= 11 ? formatarCpf(d) : formatarCnpj(d);
    }

    /* Enquanto digita: telefone e CPF/CNPJ já ficam formatados. Números só têm
       os caracteres inválidos removidos — a formatação vem ao sair do campo. */
    function aoDigitar(campo) {
        var tipo = campo.getAttribute('data-mask');

        if (tipo === 'telefone') {
            campo.value = formatarTelefone(digitos(campo.value).slice(0, 11));
        } else if (tipo === 'cpfcnpj') {
            campo.value = formatarCpfCnpj(digitos(campo.value).slice(0, 14));
        } else if (tipo === 'moeda' || tipo === 'percentual') {
            campo.value = campo.value.replace(/[^\d.,]/g, '');
        }
    }

    /* Ao sair do campo (ou ao carregar a página): formata o número. */
    function formatar(campo) {
        var tipo = campo.getAttribute('data-mask');
        if (tipo !== 'moeda' && tipo !== 'percentual') return aoDigitar(campo);

        var numero = paraNumero(campo.value);

        if (numero === null) {
            campo.value = '';
            return;
        }

        campo.value = tipo === 'moeda' ? formatarMoeda(numero) : formatarPercentual(numero);
    }

    // Delegação: funciona também em campos criados depois (ex.: nova quadra).
    document.addEventListener('input', function (evento) {
        var campo = evento.target;
        if (campo && campo.hasAttribute && campo.hasAttribute('data-mask')) {
            aoDigitar(campo);
        }
    });

    // `focusout` em vez de `blur` porque blur não sobe na árvore (não delega).
    document.addEventListener('focusout', function (evento) {
        var campo = evento.target;
        if (campo && campo.hasAttribute && campo.hasAttribute('data-mask')) {
            formatar(campo);
        }
    });

    // Formata o que já veio preenchido (repopulação após erro de validação).
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-mask]').forEach(formatar);
    });

    // Exposto para quem precisa reformatar sob demanda (ex.: taxa fixa <-> %).
    window.Mascaras = { formatar: formatar };
})();

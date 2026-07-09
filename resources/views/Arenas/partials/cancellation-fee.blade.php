{{--
    Config da taxa de cancelamento da arena.
    Reutilizado no cadastro de proprietário (Tailwind), "Nova Arena" e edição
    (Bootstrap). Por isso o estilo é PRÓPRIO (não depende de framework) e o
    esconder/mostrar usa a classe .cf--hidden (não o d-none do Bootstrap).

    Recebe (opcional) $taxaAtual no modo edição.
--}}
@php
    $taxaAtual = $taxaAtual ?? [];
    $cfCharges = (bool) old('charges_cancellation_fee', $taxaAtual['charges'] ?? false);
    $cfType = old('cancellation_fee_type', $taxaAtual['type'] ?? 'fixed');
    $cfValue = old('cancellation_fee_value', $taxaAtual['value'] ?? '');
    $cfMode = old('cancellation_fee_mode', $taxaAtual['mode'] ?? 'always');
    $cfHours = old('cancellation_fee_window_hours', $taxaAtual['hours'] ?? '');
@endphp

@once
<style>
    .cf { margin: 4px 0; }
    .cf__title { font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; }
    .cf__hint { color: #6b7280; font-size: .9rem; margin-bottom: 12px; }
    .cf__switch { display: inline-flex; align-items: center; gap: 8px; cursor: pointer;
        background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px;
        max-width: 100%; }
    .cf__switch input { width: 18px; height: 18px; flex-shrink: 0; }
    .cf__fields { display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
        margin-top: 16px; padding: 16px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fafafa; }

    /* min-width:0 é essencial: itens de grid têm min-width:auto, e a largura
       mínima de um <select> é a da sua opção mais longa ("Porcentagem do valor
       da reserva (%)"). Sem isto a coluna não encolhe e o bloco estoura o card
       em telas pequenas — mesmo já estando em uma coluna só. */
    .cf__field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }

    .cf__field > label { font-weight: 600; font-size: .9rem; color: #374151; }
    .cf__field select, .cf__field input { padding: 9px 12px; border: 1px solid #ced4da;
        border-radius: 8px; font-size: 1rem; width: 100%; max-width: 100%; background: #fff; }

    /* O texto da opção selecionada é cortado com "..." em vez de esticar o campo. */
    .cf__field select { text-overflow: ellipsis; }

    .cf__field small { color: #6b7280; font-size: .8rem; }
    .cf--hidden { display: none !important; }

    @media (max-width: 640px) { .cf__fields { grid-template-columns: 1fr; } }

    @media (max-width: 575.98px) {
        .cf__fields { padding: 12px; gap: 12px; }
        .cf__field select, .cf__field input { font-size: .9rem; padding: 8px 10px; }
        .cf__switch { padding: 8px 10px; font-size: .9rem; }
    }
</style>
@endonce

<div class="cf" data-cancel-fee>
    <div class="cf__title">Taxa de cancelamento</div>
    <p class="cf__hint">
        Cobrar uma taxa quando o cliente cancela uma reserva já <strong>confirmada</strong>?
        (Reserva pendente e cancelamento pelo dono/atendente nunca têm taxa.)
    </p>

    <label class="cf__switch">
        <input type="checkbox" name="charges_cancellation_fee" value="1"
               data-cf-toggle {{ $cfCharges ? 'checked' : '' }}>
        <span>Cobrar taxa de cancelamento</span>
    </label>

    <div class="cf__fields {{ $cfCharges ? '' : 'cf--hidden' }}" data-cf-fields>
        <div class="cf__field">
            <label>Tipo da taxa</label>
            <select name="cancellation_fee_type">
                <option value="fixed" {{ $cfType === 'fixed' ? 'selected' : '' }}>Valor fixo (R$)</option>
                <option value="percent" {{ $cfType === 'percent' ? 'selected' : '' }}>Porcentagem do valor da reserva (%)</option>
            </select>
        </div>

        <div class="cf__field">
            {{-- O rótulo e a máscara mudam conforme o tipo escolhido (ver script abaixo). --}}
            <label data-cf-valor-label>Valor {{ $cfType === 'percent' ? '(%)' : '(R$)' }}</label>
            <input type="text" inputmode="decimal" name="cancellation_fee_value"
                   data-cf-valor data-mask="{{ $cfType === 'percent' ? 'percentual' : 'moeda' }}"
                   value="{{ $cfValue }}" placeholder="{{ $cfType === 'percent' ? '30' : '20,00' }}">
            <small>Ex.: <strong>20,00</strong> = R$ 20,00 (fixo) ou <strong>30</strong> = 30% (porcentagem).</small>
        </div>

        <div class="cf__field">
            <label>Quando cobrar</label>
            <select name="cancellation_fee_mode" data-cf-mode>
                <option value="always" {{ $cfMode === 'always' ? 'selected' : '' }}>Sempre (assim que confirmada)</option>
                <option value="window" {{ $cfMode === 'window' ? 'selected' : '' }}>Só perto do horário</option>
            </select>
        </div>

        <div class="cf__field {{ $cfMode === 'window' ? '' : 'cf--hidden' }}" data-cf-window>
            <label>Horas antes do início</label>
            <input type="number" min="1" step="1" name="cancellation_fee_window_hours" value="{{ $cfHours }}">
            <small>Grátis se faltar <strong>mais</strong> que isso; com taxa a partir daí.</small>
        </div>
    </div>
</div>

@once
<script src="/js/masks.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-cancel-fee]').forEach(function (box) {
            const toggle = box.querySelector('[data-cf-toggle]');
            const fields = box.querySelector('[data-cf-fields]');
            const mode = box.querySelector('[data-cf-mode]');
            const windowField = box.querySelector('[data-cf-window]');
            const type = box.querySelector('select[name="cancellation_fee_type"]');
            const valor = box.querySelector('[data-cf-valor]');
            const valorLabel = box.querySelector('[data-cf-valor-label]');

            function sync() {
                fields.classList.toggle('cf--hidden', !toggle.checked);
                if (mode && windowField) {
                    windowField.classList.toggle('cf--hidden', mode.value !== 'window');
                }
            }

            /*
             * Troca a máscara do campo Valor conforme o tipo da taxa:
             * dinheiro (1.234,56) ou porcentagem (30,5). Reformata o que já
             * estiver digitado, senão "1.234,56" continuaria lá ao virar %.
             */
            function syncTipo() {
                if (!type || !valor) return;

                const ehPercentual = type.value === 'percent';

                valor.setAttribute('data-mask', ehPercentual ? 'percentual' : 'moeda');
                valor.placeholder = ehPercentual ? '30' : '20,00';
                if (valorLabel) valorLabel.textContent = ehPercentual ? 'Valor (%)' : 'Valor (R$)';

                if (window.Mascaras) window.Mascaras.formatar(valor);
            }

            toggle && toggle.addEventListener('change', sync);
            mode && mode.addEventListener('change', sync);
            type && type.addEventListener('change', syncTipo);

            sync();
            syncTipo();
        });
    });
</script>
@endonce

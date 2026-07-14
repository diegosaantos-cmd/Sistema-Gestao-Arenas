---
name: desconto-no-caixa
description: "Desconto (R$) ao receber reserva no caixa: grava valor líquido + desconto + motivo, visível em todos os registros"
metadata:
  type: project
---

Ao **receber uma reserva no caixa** (`CashRegisterController::pay`), dá pra aplicar
um **desconto em R$** (decisão do usuário: valor fixo, não %). Os 3 papéis que operam
o caixa podem (dono, gerente, atendente) — a rota `caixa.pay` não é `pode.gerir`.

**Regras:** desconto `0 ≤ desc ≤ total_amount`; **motivo obrigatório** quando desc > 0
(senão volta com erro `discount_reason`). O valor que entra no caixa é o **líquido**
= total − desconto.

**Dados:** migration `2026_07_13_000001_add_discount_to_payments` adicionou
`payments.discount_amount` (decimal, default 0) e `payments.discount_reason` (nullable).
O `Payment.amount` e o `CashRegisterEntry.amount` guardam o **líquido**; o desconto fica
em `discount_amount`/`discount_reason`. A descrição da entrada leva "(desconto R$ X)".
Como o faturamento real usa `cash_register_entries.amount` (líquido), já fica certo
sozinho — ver [[faturamento-real-caixa]].

**Visível em TODOS os registros da reserva** (exigência: nada oculto):
- Modal de receber (`owners/caixa/receivables`): campo desconto com **máscara de
  dinheiro** (input texto `.js-desconto-mask` com prefixo "R$"; dígitos entram como
  centavos da direita p/ esquerda → 1500 vira 15,00; capado no total). O valor numérico
  limpo vai num hidden `.js-desconto-valor` (name="discount"). O "valor a receber"
  (`.js-valor-receber`) recalcula ao vivo e o motivo (`.js-motivo-wrap`) fica obrigatório
  quando há desconto.
- `bookings/details` (compartilhado: cliente, atendente, gerente, dono): valor original
  riscado + desconto + motivo + valor pago.
- `owners/caixa/entry-details`: linha de desconto + motivo.
- `admin/arenas/_booking-list`: sob o valor, "− R$ X desc." + "pago R$ Y".

Escopo: só o recebimento no caixa. Pagamento online do cliente e reserva presencial
NÃO têm desconto (default 0). Taxa de cancelamento (`payFee`) idem.

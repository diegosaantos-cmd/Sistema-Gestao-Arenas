---
name: caixa-taxas-a-receber
description: "Card 'Taxas de cancelamento' no caixa só aparece se houver taxa a receber; hoje é sempre 0 (taxa nasce paga)"
metadata:
  type: project
---

No caixa (`owners/caixa/index`), o card **"Taxas de cancelamento"** agora só aparece
quando `$taxasCount > 0` (envolto em `@if`). Vale para dono, gerente e atendente.

**Por quê:** no modelo atual a taxa de cancelamento **sempre nasce paga** — a única
forma de gerar taxa é o CLIENTE cancelar pagando online na hora
(`Client\BookingController::cancelPayConfirm` seta `cancellation_fee_amount` e já chama
`PaymentService::registrar`). O cancelamento pelo STAFF (`BookingController::cancel`) é
"sem taxa". Logo `taxasAReceberQuery` (fee > 0 E sem payment `paid`) fica sempre vazia.

**Infra que ficou órfã de propósito (não removida):** rota/tela `caixa.fees`,
`CashRegisterController::payFee`/`taxasAReceber`, botão "receber taxa". Foram pensadas
para um cenário "cancelou, deve a taxa, paga depois no caixa" que hoje NÃO existe. Se
algum dia surgir esse fluxo (ex.: staff cancelar com taxa a receber), o card reaparece
sozinho e a tela volta a ser usada. Decisão do usuário: esconder quando zerado (não
remover). Ver [[faturamento-real-caixa]].

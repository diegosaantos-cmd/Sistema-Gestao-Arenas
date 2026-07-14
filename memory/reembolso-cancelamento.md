---
name: reembolso-cancelamento
description: "Cancelar reserva JÁ PAGA reembolsa (pago − taxa, ou tudo); saída no caixa, pendente se caixa fechado"
metadata:
  type: project
---

Cancelar uma reserva **já paga** agora **reembolsa** o cliente (antes tinha furo:
perdia o valor ou cobrava a taxa em dobro). Decisão do usuário: implementar,
cliente e staff, estilo "estorno" mas registrado no caixa.

**Regra do reembolso:**
- Cliente cancela: reembolso = valor pago − taxa (se `regraCancelamentoCliente()`
  = 'taxa'); ou o valor todo se 'livre'. A taxa fica retida (já entrou como receita).
- Staff cancela (dono/gerente/atendente): reembolso **integral** (sem taxa — quem
  cancelou foi a arena, o cliente não é penalizado).

**Dados:** migration `2026_07_13_000002_add_refund_to_payments` → `payments`:
`refunded_at`, `refund_amount`, `refund_cash_register_entry_id`.

**Serviço:** `PaymentService::reembolsar($booking, $taxa, $createdBy)` — acha o
pagamento pago não estornado, calcula (amount − taxa), marca estornado, e:
- caixa ABERTO → cria SAÍDA (expense) "Reembolso cancelamento reserva #X" e vincula;
- caixa FECHADO → fica **pendente** (refund_cash_register_entry_id null) e é lançado
  **MANUALMENTE** (decisão do usuário: igual aos "pagamentos a lançar", não automático).
  Tela `caixa.pending-refunds` ("Reembolsos a lançar") + ação `caixa.launch-refund` →
  `PaymentService::lancarReembolso`. Card no caixa só aparece se `reembolsosCount > 0`.
  `reembolsosALancarQuery` = refunded_at not null, refund_amount>0, refund_entry null.
  Ver [[faturamento-real-caixa]].

**Faturamento fica certo sozinho:** entrou o pagamento (receita) + saiu o reembolso
(despesa) → líquido = a taxa (ou 0). O reembolso cai em "despesas" do resumo.
"Total gasto" do cliente usa `cancellation_fee_amount` (= taxa retida, ou 0).

**UI/fluxo:** cliente — `client/bookings/_card`: reserva paga não vai mais pro
`cancel-pay` (pagar taxa); abre o modal de "Cancelar" mostrando o reembolso e faz
POST em `client.bookings.cancel` (que detecta isPaga e reembolsa). Guards em
`cancelPay`/`cancelPayConfirm` barram pagar taxa de reserva já paga. Staff —
`BookingController::cancel` reembolsa integral se paga. Notificação ao cliente
(`notificarClienteReembolso`) + o reembolso aparece nos detalhes da reserva
("a lançar no caixa" quando pendente).

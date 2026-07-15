---
name: excluir-quadra
description: "Excluir quadra = soft delete (espelha excluir arena): cancela reservas futuras com motivo, mantém histórico; Booking::court() agora inclui trashed"
metadata:
  type: project
---

Gestão de quadras (`/quadras`) tem **Excluir** (vermelho, `btn-danger`) além de
Editar/Desativar. Espelha o excluir da arena:

- `QuadraController::destroy(Court $quadra)`: se **não há reserva futura** (pending/
  confirmed com data >= hoje) → **soft delete** direto; se há → mostra
  `courts.delete-confirm` (tabela dos afetados + motivo).
- `confirmDelete()` (rota `quadras.delete.confirm`, POST): cancela as futuras com o
  motivo e faz o soft delete numa transação. **Bulk cancel direto** (igual arena/
  desativar) — NÃO dispara reembolso mesmo que a reserva esteja paga; se algum dia
  reembolso em massa for desejado, afeta também o excluir/desativar da arena.
- Court já usa `SoftDeletes` → registros preservados. Some das listagens ativas
  porque elas usam `Arena::courts()` (respeita soft delete).

**Mudança importante 1 — nome no histórico:** `Booking::court()` agora é
`belongsTo(Court::class)->withTrashed()` — as ~20 telas que mostram `$b->court->name`
continuam exibindo o nome da quadra excluída (não "perde" o registro), sem editar cada
view. `courtWithTrashed()` continua existindo (redundante, usado no admin).

**Mudança importante 2 — reserva não some do dono/caixa/admin:** as telas do lado
gestor montam o conjunto de reservas da arena por `$arena->courts()->pluck('id')` /
`->select('id')`, que EXCLUI soft-deleted → reserva de quadra excluída sumia do dono
(mas aparecia no cliente, que filtra por client_id). Corrigido para
`$arena->courts()->withTrashed()->pluck('id')` em TODOS os levantamentos de court_id
para filtrar reservas (BookingController, ClientController, Owner\CashRegisterController,
ArenaController, Admin\DashboardController e as closures de dashboard em routes/web.php).
Regra: para LISTAR/contar reservas da arena use `withTrashed()`; para listar quadras
ATIVAS (dropdown, criar reserva, tela da arena) use `courts()` normal. Ver [[reserva-cliente-nulo]].

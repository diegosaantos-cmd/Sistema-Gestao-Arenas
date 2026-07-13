---
name: area-atendente
description: "Painel do atendente (employee 'basic'): consulta arena/quadras por modal, opera reservas/caixa; gestão bloqueada por pode.gerir"
metadata:
  type: project
---

O ATENDENTE (Employee `access_level` 'basic', ativo) tem painel próprio em
`resources/views/employees/dashboard.blade.php` (rota closure `employees.dashboard`
em routes/web.php). Reaproveita o visual do painel do dono, mas restrito.

**O que ele pode:**
- Consultar arena e quadras — por MODAL read-only no painel (não abre as telas de
  edição do dono; `arenas.show`/`quadras.index` são telas de EDIÇÃO).
- **Ver CLIENTES** (card no painel): lista (`clients.index`), detalhes (`clients.show`)
  e reservas do cliente (`clients.bookings`) — precisa conhecer o cliente no balcão.
  Mas NÃO envia mensagem nem disparo em massa (some o botão; rota sob pode.gerir).
- Reservas de hoje + aguardando confirmação (confirmar/cancelar/reagendar).
- Registrar reserva presencial; operar o CAIXA (abrir/fechar, lançar, receber) —
  SEM relatório financeiro/balanço (o card "Lucro do mês" não aparece pra ele).
- "Minha Conta" (employee.profile): só VISUALIZA os dados pessoais, mas PODE trocar
  a senha (updatePersonal aborta 403 se ehAtendente).
- **Recebe as notificações de staff** (sino + e-mail): "Nova reserva pendente" e
  "Reserva cancelada pelo cliente". `UserNotification::idsStaffDaArena` passou a
  incluir TODO funcionário ativo (antes só dono + gerentes managerial).

**Não pode (some do painel e bloqueado por URL):** cards Funcionários/Lucro,
cadastrar/editar quadras, funcionários, arena, os relatórios do caixa, e
enviar/disparar mensagens a clientes.

**Como o acesso funciona:**
- `App\Support\ArenaAtual` agora resolve a arena do atendente também (`empregado()`
  = vínculo ativo; `gerente()`=managerial, `atendente()`=basic; helpers `ehAtendente()`,
  `podeGerir()`= dono||gerente, `rotulo()`). Ver [[area-gerente]].
- Middleware **`pode.gerir`** (App\Http\Middleware\PodeGerirArena, alias em
  bootstrap/app.php) barra o atendente nas rotas de gestão: grupo inteiro de
  arenas/quadras/employees (web.php ~602) + subgrupos owner.profile.*,
  owners.arena.choose/select, caixa.report/report.entries/balance, e as mensagens
  de cliente (clients.broadcast*/clients.message*). Já clients.index/show/bookings
  ficam FORA do pode.gerir (liberadas ao atendente). Dono/gerente passam em tudo.

**Links vazando: verificado que NÃO há.** Rendered todas as telas que o atendente
acessa (caixa.index, bookings today/pending/index/history, presencial, bookings.details)
logado como atendente e varri todos os href/action: zero links para rotas bloqueadas.
Os links de relatório/balanço/lucro só existem no painel do dono e nas telas de
relatório; os de clientes só dentro das telas de clientes; os de quadras/arenas/
funcionários só no painel do dono. O atendente não vê nenhum deles. Nada a podar.

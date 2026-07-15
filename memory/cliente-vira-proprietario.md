---
name: cliente-vira-proprietario
description: "Cadastro de arena por cliente logado: escolhe usar a conta atual (vira owner, client soft-deleted, histórico mantido) ou criar dados novos (mantém cliente)"
metadata:
  type: project
---

No cadastro de arena (`registerArenaOwners`, RegisterArenaOwnerController), quando é
um **cliente logado** (`type==='client'`), a Etapa 1 mostra a escolha `modo_conta`:

- **atual**: vira proprietário com a MESMA conta (email/senha/nome/telefone iguais).
  O registro de cliente é **soft-deleted** (`$user->client()->delete()`) e o
  `users.type` vira `owner`. Reservas antigas continuam com o nome no histórico
  porque `Booking::client()` usa `withTrashed()`. Ele **some das listas de clientes**
  ativas (Client::query sem trashed) = "cliente que não existe".
- **novos**: cria uma conta de proprietário SEPARADA (email/senha próprios, email
  precisa ser único → email repetido dá erro). A conta de cliente dele fica intacta.

Detalhes:
- Cliente logado → `create()` passa `$ehClienteLogado`; a view mostra os radios e,
  via JS, ao escolher "atual" **esconde e DESABILITA** os campos de acesso (o wizard
  pula `disabled` e não são enviados; servidor reaproveita a conta logada).
- No `store()`, `$usarContaAtual` só é true se cliente logado + `modo_conta==='atual'`.
  As regras de nome/email/senha só valem no modo "novos". O email da arena aceita o
  email do próprio dono (`emailPertenceAOutroUsuario` recebe `auth()->id()` no modo atual).
- **Client** agora usa `SoftDeletes` (migration 2026_07_14_000002). Soft delete
  preserva as FKs (arena_favorites.client_id etc.). Ver [[reserva-cliente-nulo]] e
  [[excluir-quadra]] (mesmo padrão withTrashed do Booking::court()).

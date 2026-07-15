---
name: otimizacoes-escala
description: "Duas otimizações O(n) anotadas para quando escalar muito (numeração via coluna; busca FULLTEXT) — hoje não precisa"
metadata:
  type: project
---

As listas que crescem já **paginam** (reservas, histórico, clientes, notificações,
arenas, caixas fechados — staff e cliente; 12–30/página). O risco de "carregar tudo
de uma vez e travar" está resolvido para escala realista.

Ficaram **dois custos O(n)** anotados para OTIMIZAR SÓ SE uma arena chegar a
**dezenas de milhares** de registros (hoje é desprezível — NÃO fazer agora):

1. **Numeração "Nº na arena"** (`Booking::numerosNaArena`): monta um mapa
   `[id → número]` puxando TODOS os ids de reserva da arena a cada render das
   listagens (evita N+1, mas carrega o conjunto inteiro; paginação não corta isso
   porque o número é a posição no total). **Fix quando precisar:** gravar o número
   numa COLUNA `numero_na_arena` na criação da reserva → só ler, sem calcular.
   Ver [[reserva-cliente-nulo]] (a numeração já trata client_id nulo).

2. **Busca `LIKE '%texto%'`** (histórico por cliente/quadra, lista de clientes,
   navegar arenas): o `%` no início impede uso de índice B-tree → varredura O(n).
   As buscas com `LIKE 'texto%'` (prefixo, ex.: e-mail) já usam índice. **Fix quando
   precisar:** índice **FULLTEXT** + `MATCH ... AGAINST` nas colunas pesquisadas.

Também dá pra transformar `autoConfirmarExpiradas`/`autoCompletarRealizadas` (faxina
de status que roda a cada tela) num job agendado — mesma lógica: só se crescer muito.

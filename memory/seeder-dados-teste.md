---
name: seeder-dados-teste
description: "DadosDeTesteSeeder cria cenário completo de teste (6 logins senha 12345678); separado do db:seed padrão; idempotente"
metadata:
  type: reference
---

Cenário de teste local: `database/seeders/DadosDeTesteSeeder.php`. Rodar com
`php artisan db:seed --class=DadosDeTesteSeeder` (NÃO está no `db:seed` padrão para
não sujar produção). **Idempotente** (usa updateOrCreate por e-mail/nome; recria as
reservas do cenário do zero a cada run). Todos os logins usam senha **12345678**.

Usuários: `adminteste@gmail.com` (admin, vem do AdminUserSeeder), `dono@teste.com`
(owner), `gerente@teste.com` (employee managerial), `atendente@teste.com` (employee
basic), `cliente@teste.com` e `cliente2@teste.com` (client). Cria "Arena Teste" com
3 quadras, horários, formas de pagamento, 2 funcionários, 6 reservas em estados
variados (confirmed/pending/cancelled/completed/presencial), pagamentos e 1 caixa
aberto. Senha grava em `password_hash` (cast hashed) — por isso seeder, não SQL direto.

O `db:seed` padrão (`DatabaseSeeder`) só roda `PaymentMethodSeeder` (pix/card/cash) +
`AdminUserSeeder`. Documentado nas seções 10-11 de `docs/documento-tecnico.html`.
Ver [[manter-manual-instalacao]] — se o fluxo de instalação/teste mudar, atualizar
docs/documento-tecnico (HTML) + regerar o PDF (Chrome headless).

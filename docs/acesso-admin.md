# Acesso do administrador do sistema

O administrador padrão é criado automaticamente pela seed
`Database\Seeders\AdminUserSeeder` (chamada dentro do `DatabaseSeeder`),
então ele existe em qualquer máquina após rodar as seeds.

## Credenciais

| Campo | Valor |
|-------|-------|
| Nome  | `admin` |
| E-mail (login) | `adminteste@gmail.com` |
| Senha | `12345678` |

> O e-mail é armazenado em minúsculas porque o sistema normaliza o e-mail no
> login. Mesmo digitando `adminTeste@gmail.com`, a conta é encontrada.

## Como (re)criar

```bash
# cria/atualiza só o admin (idempotente, não duplica)
php artisan db:seed --class=AdminUserSeeder

# ou junto de tudo (formas de pagamento + admin)
php artisan db:seed

# banco do zero + seeds
php artisan migrate:fresh --seed
```

## Detalhes técnicos

- Cria uma linha em `users` com `type = 'admin'`, `active = true` e a senha já
  com hash (cast `hashed` do model `User`).
- Cria o vínculo em `system_admins` (estrutura usada pelas telas de
  administradores do sistema).
- É idempotente: usa `updateOrCreate` pelo e-mail, então rodar várias vezes
  não gera duplicados.

> ⚠️ Estas são credenciais de desenvolvimento/teste. Em produção, troque a
> senha e o e-mail padrão.

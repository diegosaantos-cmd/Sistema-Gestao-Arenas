---
name: limpeza-jetstream
description: "UI Livewire do Jetstream removida (perfil/2FA/api/teams); auth é Fortify + views Bootstrap próprias; só sobrou o componente x-back"
metadata:
  type: project
---

O app usa **Fortify** (backend de auth) com **views Bootstrap próprias** (layouts.main).
A UI Livewire do Jetstream (perfil, dashboard, api, teams, navigation-menu) foi
**removida** por não ser usada. Estado após a limpeza:

- **2FA DESLIGADO**: `Features::twoFactorAuthentication` removido de `config/fortify.php`
  (nunca funcionou — sem colunas `two_factor_*` em users e sem tela de ativação).
- **Views apagadas**: `profile/*` (Jetstream), `navigation-menu`, `layouts/app`,
  `layouts/guest`, `auth/two-factor-challenge`, `auth/confirm-password`, `api/*`,
  `emails/team-invitation`, e **todos os componentes `components/*` EXCETO `back.blade.php`**
  (único componente do app). Também apagados órfãos: `funcionario/`, `proprietario/`,
  `admin/owners/_company-switcher`, `components/welcome`.
- **Views reescritas p/ Bootstrap** (layouts.main): `terms`, `policy`, `verify-email`
  (antes usavam `x-guest-layout` do Jetstream). `login/register/forgot/reset` já eram.
- **Rotas redirecionadas** em routes/web.php (sobrescrevem Jetstream/Fortify, pois a
  ÚLTIMA rota registrada por URI vence): `/user/profile` → perfil próprio por papel;
  `/user/confirm-password` → dashboard. Sem isso, apagar as views daria 500.
- **Controller apagado**: `UserController` (importado mas não usado em routes/api.php).

Sobra de código morto tolerada (não removida p/ evitar risco): no `FortifyServiceProvider`
ainda há `RateLimiter::for('two-factor')` e `Fortify::redirectUserForTwoFactorAuthenticationUsing`
— inofensivos com 2FA off. O pacote Jetstream continua no composer (fornece traits
HasProfilePhoto/HasApiTokens no User); só a UI dele foi removida.

Como validar após mexer em views: rodar o analisador de referências (0-ref por view,
case-insensitive, componentes por `<x-nome`) + renderizar login/register/terms/policy/welcome.

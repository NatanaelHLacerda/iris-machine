# REASONS — REQ-001 Acesso ao `/gestao-crm`
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## R — Requirements

**Problema:** não há área administrativa protegida.
**Critérios de sucesso:**
- [ ] `GET /gestao-crm` sem sessão → 302 para `crm.login`.
- [ ] Login válido → 302 para `crm.dashboard`; sessão regenerada.
- [ ] Login inválido → volta com erro genérico em `email`.
- [ ] 6ª tentativa errada em 60s para o mesmo e-mail+IP → erro de limite com segundos restantes.
- [ ] `POST /gestao-crm/logout` encerra a sessão → `crm.login`.
- [ ] Usuário logado em `GET /gestao-crm/login` → `crm.dashboard`.
- [ ] `crm:create-admin` cria usuário válido; e-mail duplicado falha com mensagem.
- [ ] Páginas do CRM respondem com `X-Robots-Tag: noindex, nofollow`.

## E — Entities

- **User** (existente): `name`, `email` (único), `password` (hashed).

## A — Approach

Auth nativa: `Auth::attempt` + `RateLimiter`, guard `web`. Layout Blade próprio do CRM com bundle Vite separado (`crm.css`/`crm.js`). Alternativa Fortify rejeitada: dependência nova para um único admin.

## S — Structure

```text
routes/web.php                         grupo prefix('gestao-crm')->name('crm.')
app/Http/Controllers/Crm/LoginController.php
app/Http/Controllers/Crm/DashboardController.php   (placeholder até REQ-008)
app/Http/Requests/Crm/LoginRequest.php
app/Http/Middleware/NoIndex.php
app/Console/Commands/CreateAdminCommand.php
resources/views/crm/layouts/app.blade.php          sidebar + main
resources/views/crm/layouts/guest.blade.php        login
resources/views/crm/auth/login.blade.php
resources/views/crm/partials/sidebar.blade.php
resources/views/crm/partials/page-header.blade.php (barra da tela reutilizável)
resources/css/crm.css · resources/js/crm.js
vite.config.js                                     + entradas do CRM
bootstrap/app.php                                  redirectGuestsTo / redirectUsersTo
```

## O — Operations

1. `vite.config.js`: adicionar `resources/css/crm.css` e `resources/js/crm.js` em `input`.
2. `bootstrap/app.php`: `$middleware->redirectGuestsTo(fn () => route('crm.login'))` e `redirectUsersTo(fn () => route('crm.dashboard'))`; alias `noindex` → `NoIndex`.
3. Rotas:
   - `middleware(['noindex'])`, `prefix('gestao-crm')`, `name('crm.')`:
     - `guest`: `GET login` → `LoginController@create` (`login`); `POST login` → `LoginController@store` (`login.store`).
     - `auth`: `GET /` → `DashboardController` (`dashboard`); `POST logout` → `LoginController@destroy` (`logout`).
4. `LoginRequest`: regras `email: required|string|email`, `password: required|string`; método `authenticate(): void` que chama `ensureIsNotRateLimited()`, `Auth::attempt(only email,password, $this->boolean('remember'))`, em falha `RateLimiter::hit($this->throttleKey(), 60)` e `ValidationException` com `__('auth.failed')`; em sucesso `RateLimiter::clear`. `throttleKey(): string` = `Str::transliterate(Str::lower(email).'|'.ip)`. Limite: 5.
5. `LoginController::create(): View`; `store(LoginRequest): RedirectResponse` → `authenticate()`, `session()->regenerate()`, `redirect()->intended(route('crm.dashboard'))`; `destroy(Request): RedirectResponse` → `Auth::logout()`, `invalidate()`, `regenerateToken()`, redirect `crm.login`.
6. `NoIndex::handle()` adiciona o header `X-Robots-Tag: noindex, nofollow`.
7. `CreateAdminCommand` (`crm:create-admin {--name=} {--email=}`): pede o que faltar e a senha com `secret`, valida (`email` único, senha mín. 8) com `Validator`, cria `User`, retorna `self::SUCCESS`/`FAILURE`.
8. Layout `crm.layouts.app`: `<meta name="robots" content="noindex">`, fontes da landing, `@vite(crm)`, sidebar com grupos:
   - Growth: Dashboard.
   - Marketing: Funil da LP, Meta Ads.
   - Vendas: Pipeline, Leads, Vendas.
   - Financeiro: Caixa.
   - Itens de REQs futuros aparecem só quando a rota existir (`Route::has`).
9. Sidebar: logo "IRIS MACHINE" (Orbitron) + "Gestão CRM"; item ativo via `request()->routeIs()`; cartão do usuário com iniciais, nome e botão sair (form POST). No mobile (<900px): sidebar off-canvas com botão de menu (`crm.js`).
10. Partial `page-header` com slots: `eyebrow`, `title`, `total`, ações (filtros/botões).

## N — Norms

Ver `spdd/README.md`. Mensagens em pt-BR. Botão primário rosa, item ativo da sidebar com filete violeta e fundo `--surface`.

## S — Safeguards

- Nunca expor se o e-mail existe (erro único para e-mail ou senha).
- Nunca rota pública de cadastro.
- Senha nunca em log nem em output do comando.
- CSRF em login e logout (padrão do Laravel).
- Não alterar o layout nem o bundle da landing.

## Testes

Feature `Crm/LoginTest`: redirect de convidado, login ok, login inválido, rate limit, logout, logado em `/login`, header noindex. Feature `Crm/CreateAdminCommandTest`: cria admin e recusa e-mail duplicado.

## Sincronização com o código (2026-10-23)

- Componentes Blade anônimos ficaram em `resources/views/components/crm/` (`<x-crm.page-header>` etc.), exigência do Laravel para componentes anônimos; o `<head>` do CRM é o partial `crm/layouts/head.blade.php`.
- `DashboardController` deixou de ser placeholder no REQ-008.

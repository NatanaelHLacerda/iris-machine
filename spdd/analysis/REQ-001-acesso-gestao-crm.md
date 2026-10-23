# Análise — REQ-001 Acesso ao `/gestao-crm`
**Status:** RASCUNHO

## Conceitos

- **Entidades:** `User` (admin), sessão.
- **Operações:** autenticar, sair, criar admin, proteger rota, limitar tentativas.

## O que existe

| Item | Estado |
|---|---|
| `app/Models/User.php` | Padrão Laravel, `password` com cast `hashed`, atributos `#[Fillable]`/`#[Hidden]` |
| `database/migrations/0001_01_01_000000_create_users_table.php` | Tabelas `users`, `password_reset_tokens`, `sessions` |
| `config/auth.php` | Guard `web` (sessão), provider Eloquent |
| `routes/web.php` | Só `GET /` → `HomeController` |
| `bootstrap/app.php` | Middleware sem customização; redirect de convidado ainda não configurado |
| Starter kit (Breeze/Fortify) | Ausente |
| `resources/views/layouts/app.blade.php` | Layout da landing, com cortina e scripts de animação, impróprio para o CRM |
| `.env` | `SESSION_DRIVER=file`, `DB_CONNECTION=sqlite` |

## Referência (crm-frontend)

`src/App.tsx`: sidebar 260px escura, grupos com título em caixa alta, item ativo destacado, cartão do usuário no rodapé com iniciais e botão sair. `src/rotas.tsx`: guarda `Protegida` (sem sessão → `/login` guardando destino), `Publica` (com sessão → destino).

## Riscos

- **Força bruta:** login sem limite. Mitigar com `RateLimiter` por e-mail + IP.
- **Fixação de sessão:** regenerar a sessão no login e invalidar no logout.
- **Indexação:** páginas do CRM em buscador. `X-Robots-Tag: noindex` / meta robots.
- **Mistura de estilos:** o CSS da landing tem resets e animações globais; o CRM precisa de layout e bundle próprios.

## Direções de design

1. **Auth manual com `Auth::attempt`** (recomendada): um controller, um Form Request, um rate limiter. Pouco código e nenhuma dependência.
2. Fortify headless: mais completo (reset e 2FA prontos), mas é dependência nova e excesso para um admin.

## Impacto

Novo layout `crm`, novo par CSS/JS no Vite, novo grupo de rotas. A landing não muda.

# Iris Machine

Plataforma para orquestrar agentes de IA hospedados em VPS. Monorepo React (front) + Fastify (back), com autenticação via Supabase.

## Estrutura

```
iris-machine/
├── apps/
│   ├── api/          # Fastify + TypeScript — API de autenticação e agentes
│   └── web/          # React 18 + Vite + React Router
├── packages/
│   └── shared/       # Tipos e schemas Zod compartilhados entre web e api
└── prototypes/       # Protótipos .dc.html originais (referência de design)
```

Gerenciador: **pnpm workspaces**. Node **20+**.

## Setup

```bash
pnpm install

cp apps/api/.env.example apps/api/.env
cp apps/web/.env.example apps/web/.env
```

Preencha `apps/api/.env` com as credenciais do projeto Supabase
(*Project Settings → API*):

| Variável | Onde encontrar |
|---|---|
| `SUPABASE_URL` | Project URL |
| `SUPABASE_ANON_KEY` | `anon` `public` key |
| `SUPABASE_SERVICE_ROLE_KEY` | `service_role` key — **apenas no back-end**, nunca no front |

No painel do Supabase, em *Authentication → Providers*, habilite **Email**.
Se "Confirm email" estiver ligado, o cadastro devolve `emailConfirmationRequired: true`
e o usuário só entra depois de confirmar o e-mail.

## Rodando

```bash
pnpm dev          # web (5173) + api (3333) em paralelo
pnpm dev:web
pnpm dev:api
pnpm build
pnpm typecheck
```

## API de autenticação

Base: `http://localhost:3333`

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| POST | `/auth/signup` | — | Cria conta (`email`, `password`, `name?`) |
| POST | `/auth/signin` | — | Login; devolve `accessToken` e grava cookie de refresh |
| POST | `/auth/refresh` | cookie | Renova o par de tokens |
| POST | `/auth/signout` | Bearer | Revoga a sessão e limpa o cookie |
| GET | `/auth/me` | Bearer | Usuário da sessão atual |
| POST | `/auth/password/reset` | — | Dispara e-mail de recuperação (sempre 204) |
| PATCH | `/auth/password` | Bearer | Troca a senha do usuário logado |

Rotas de agentes (todas exigem Bearer): `GET /agents`, `GET /agents/stats`,
`GET /agents/:id`, `GET|PUT /agents/:id/config`, `GET|POST /agents/:id/messages`.

### Modelo de sessão

- O **refresh token** vive num cookie `httpOnly` — inacessível ao JavaScript do navegador.
- O **access token** (curta duração) volta no corpo da resposta e é mantido **só em memória**
  no front (`apps/web/src/lib/api.ts`), nunca em `localStorage`.
- Em `401`, o cliente renova a sessão uma vez e repete a requisição; requisições
  simultâneas compartilham o mesmo refresh.
- Todo access token é validado contra o Supabase a cada requisição — o back-end não
  decodifica o JWT por conta própria.

Proteções: CORS restrito a `WEB_ORIGIN` com `credentials`, rate limit global (120/min)
e reforçado nas rotas de credencial (10/min), mensagens de login genéricas e reset de
senha que sempre responde 204 — ambos para não permitir enumeração de usuários.

Em produção o cookie usa `secure` + `SameSite=None`, então **a API precisa estar sob HTTPS**.
Se front e API ficarem no mesmo domínio, troque para `SameSite=Lax` em
`apps/api/src/lib/session-cookie.ts`.

## Deploy

O front e a API vão para hosts diferentes — a Vercel não roda servidor Node persistente.

**Front (`apps/web`) → Vercel.** O `vercel.json` da raiz já define build e output;
importe o repositório sem alterar o Root Directory. Uma única variável de ambiente:

- `VITE_API_URL` — URL pública da API

Variável com prefixo `VITE_` é embutida no bundle que vai ao navegador. Nunca coloque
chave secreta ali.

**API (`apps/api`) → Render.** O `render.yaml` da raiz é um Blueprint: em New → Blueprint,
aponte para o repositório e o Render pede os valores de `SUPABASE_URL`, `SUPABASE_ANON_KEY`,
`SUPABASE_SERVICE_ROLE_KEY`, `WEB_ORIGIN` e `PASSWORD_RESET_REDIRECT_URL`.

`WEB_ORIGIN` precisa ser exatamente a URL da Vercel, com `https://` e sem barra final,
ou o navegador bloqueia as chamadas por CORS. `NODE_ENV=production` é o que liga
`Secure` + `SameSite=None` no cookie de refresh, necessário porque front e API ficam
em domínios distintos — e por isso a API tem que estar sob HTTPS.

## Estado atual

Autenticação, agentes, configurações e mensagens estão persistidos no Supabase
(`supabase/migrations/0001_agents_persistence.sql`). Rode essa migration no seu
projeto (SQL Editor do Supabase ou `supabase db push`) antes de subir a API —
sem ela `GET /agents` e afins respondem `500 supabase_not_configured`/erro de tabela
inexistente.

Tabelas: `agents`, `agent_configs`, `messages`. RLS ligada sem nenhuma policy —
só a API, via `SUPABASE_SERVICE_ROLE_KEY`, consegue ler/gravar; `anon` e
`authenticated` ficam bloqueados por completo (o front nunca fala com essas
tabelas direto, só através da API).

`conversations`, `lastRunAt` e `status` do agente nunca são armazenados como
valor fixo — são calculados a cada request a partir de `messages` e de um
reachability check real no Hermes (`GET /agents/stats` idem, agregando todos
os agentes). Isso elimina os números mockados que existiam antes no painel.

## Telas

| Rota | Origem |
|---|---|
| `/` | `prototypes/Landing.dc.html` — hero + login/cadastro |
| `/painel` | `prototypes/CRM.dc.html` — lista de agentes e métricas |
| `/agentes/:agentId/conversa` | `prototypes/Chat.dc.html` |
| `/agentes/:agentId/configuracao` | `prototypes/JimmyConfig.dc.html` |

Os `.dc.html` ficam em `prototypes/` só como referência visual; não fazem parte do build.

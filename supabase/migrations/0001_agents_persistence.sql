-- Substitui o armazenamento em memória de apps/api/src/routes/agents.ts por
-- tabelas reais. Nenhuma dessas tabelas é acessada pelo front-end diretamente:
-- só a API (via SUPABASE_SERVICE_ROLE_KEY) fala com elas. Por isso RLS fica
-- ligada sem nenhuma policy — bloqueia `anon` e `authenticated` por completo,
-- e a service role sempre ignora RLS.

create extension if not exists pgcrypto;

create table if not exists agents (
  id text primary key,
  name text not null,
  role text not null,
  instructions text not null default '',
  model text not null,
  avatar_url text,
  active boolean not null default true,
  vps_address text,
  created_at timestamptz not null default now()
);

alter table agents enable row level security;

create table if not exists agent_configs (
  agent_id text primary key references agents (id) on delete cascade,
  config jsonb not null,
  updated_at timestamptz not null default now()
);

alter table agent_configs enable row level security;

create table if not exists messages (
  id uuid primary key default gen_random_uuid(),
  agent_id text not null references agents (id) on delete cascade,
  author text not null check (author in ('user', 'agent')),
  content text not null,
  created_at timestamptz not null default now()
);

alter table messages enable row level security;

-- Índice de suporte às consultas de métricas (conversas de hoje, última
-- atividade, tempo médio de resposta) em apps/api/src/routes/agents.ts.
create index if not exists messages_agent_id_created_at_idx
  on messages (agent_id, created_at);

-- Seed: agente único hoje hardcoded em agents.ts. Ajuste/apague conforme
-- necessário — é só o ponto de partida pra não perder o registro existente.
insert into agents (id, name, role, instructions, model, avatar_url, active, vps_address)
values (
  'jimmy',
  'Jimmy',
  'Conversão e portabilidade de código entre plataformas',
  'Analisa projetos em diferentes linguagens e frameworks, e reconstrói cada tela ou componente na plataforma de destino — pronto para integrar, já validado e revisado.',
  'Claude Opus',
  '/uploads/761a440f4d38e77c845b67badf122797.jpg',
  true,
  '72-60-126-228.sslip.io'
)
on conflict (id) do nothing;

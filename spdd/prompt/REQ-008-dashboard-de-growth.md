# REASONS — REQ-008 Dashboard de growth
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## R — Requirements

**Problema:** métricas espalhadas; sem visão de meta e gargalo.
**Critérios de sucesso:**
- [ ] `GoalProgress::for($month, $today)` → `goal, revenue, percent, remaining, daily_needed, projection, status` (status pela `DP-0801`).
- [ ] `FunnelMetrics::steps($period, $filters)` → 8 passos com `count`, `rate_from_previous` (`null` se anterior = 0) e `bottleneck` no menor rate.
- [ ] Cada KPI traz `value`, `previous`, `delta_percent` (`null` sem base).
- [ ] Rotas `crm.dashboard` (visão geral), `crm.dashboard.tab` com `{tab}` ∈ funil|aquisicao|receita; aba inválida → visão geral.
- [ ] `PATCH crm.settings.goal` altera a meta.

## E — Entities

- **Setting** `monthly_goal_cents` (padrão 400000).
- DTOs (readonly): `Kpi{label, value, previous, delta_percent, format}`, `FunnelStep{key, label, count, rate_from_previous}`, `GoalStatus` enum (OnTrack, Attention, OffTrack).

## A — Approach

`GrowthDashboard` compõe `GoalProgress`, `FunnelMetrics`, `RevenueMetrics`, `AdMetrics`, `CashFlow` para o período e o período anterior equivalente. Gráficos em SVG inline gerados por componentes Blade (barras, linha, funil). Abas por rota, como na referência.

## S — Structure

```text
app/Support/Crm/{GrowthDashboard,GoalProgress,FunnelMetrics,Period,TopicExtractor}.php
app/Support/Crm/Data/{Kpi,FunnelStep}.php · app/Enums/GoalStatus.php
app/Http/Controllers/Crm/{DashboardController,GoalSettingController}.php
resources/views/crm/dashboard/{overview,funnel,acquisition,revenue}.blade.php
resources/views/crm/components/{kpi,goal-meter,bar-chart,line-chart,funnel-chart,period-filter,source-note}.blade.php
```

## O — Operations

1. `Period::fromRequest(Request): CarbonPeriod` (atalhos `mes`, `mes-anterior`, `7d`, `30d`, `90d`, `custom&from&to`; fuso SP) e `Period::previous(CarbonPeriod): CarbonPeriod` (mesmo tamanho imediatamente antes).
2. `GoalProgress::for(CarbonImmutable $month, CarbonImmutable $today): array`:
   - `revenue` = `RevenueMetrics::revenueForMonth`;
   - `projection` = revenue + cobranças pendentes com vencimento até o fim do mês + (média diária de pagamentos avulsos dos últimos 30 dias × dias restantes);
   - `daily_needed` = max(0, goal − revenue) ÷ dias restantes (inclusive hoje); `status` pela razão projection ÷ goal.
3. `FunnelMetrics::steps(CarbonPeriod, array $filters): Collection<FunnelStep>`:
   - visitas/cta/form_view/form_start: `count(distinct session_id)` por tipo, filtrável por `channel`/`utm_campaign`;
   - lead: leads criados no período (mesmos filtros);
   - conversa/proposta/cliente: desses leads, os que têm `LeadActivity StatusChanged` com `to` ≥ etapa (ou status atual ≥ etapa, ignorando Perdido).
4. `TopicExtractor::top(Collection $goals, int $limit = 10): array<string,int>` (minúsculo, sem acento, stopwords pt-BR, palavras ≥ 4 letras).
5. `GrowthDashboard`:
   - `overview($period): array{goal, kpis, actions}` — KPIs: faturado, MRR, alunos ativos, leads, conversão lead→cliente, investido, ROAS, saldo; `actions`: contagens de follow-up atrasado, esfriando, cobrança atrasada com links para as listas filtradas;
   - `funnel($period, $filters)`;
   - `acquisition($period)`: por canal e por campanha (leads, clientes, receita, conversão), por `level` e `plan_interest`, `TopicExtractor` sobre `goal`;
   - `revenue()`: 12 meses de faturamento, MRR atual, churn do mês, LTV, ticket médio por plano, upsell.
6. `DashboardController::__invoke(Request, ?string $tab = null)`: escolhe a view da aba.
7. Componentes: `goal-meter` (barra com marca de "onde deveria estar hoje"), `kpi` (valor, delta com seta e texto "vs período anterior"), `funnel-chart` (barras horizontais com % entre passos e selo "gargalo"), `bar-chart`/`line-chart` SVG com `role="img"` e `aria-label`, `source-note` (de onde vem o dado).
8. Sidebar: "Dashboard" com sub-itens Visão geral, Funil, Aquisição, Receita (padrão da referência).

## N — Norms

Ver `spdd/README.md`. Rosa só no destaque da meta e no gargalo; violeta na estrutura dos gráficos; números `tabular-nums`.

## S — Safeguards

- Nenhum número sem base: `null` → "—" com `source-note` explicando.
- Nenhum cálculo de métrica duplicado: o dashboard só chama os serviços.
- Consultas agregadas (sem carregar todos os registros em memória), exceto `goal` para o `TopicExtractor` (limitado ao período).

## Testes

Unit: `GoalProgress` (meio do mês, meta batida, último dia), `FunnelMetrics` (taxas, `null`, gargalo, filtro), `Period` (atalhos, anterior), `TopicExtractor`. Feature: cada aba responde 200 com dados de factory, aba inválida → visão geral, alterar meta, convidado → login.

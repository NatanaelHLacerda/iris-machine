# Análise — REQ-008 Dashboard de growth
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## Conceitos

- **Entidades:** nenhuma nova; lê `TrackingEvent`, `Lead`, `LeadActivity`, `Sale`, `Subscription`, `Payment`, `AdSpend`, `CashEntry`, `Setting` (`monthly_goal_cents`).
- **Operações:** calcular meta e projeção, montar funil, comparar períodos, segmentar, listar ações do dia.

## O que existe (após 002–007)

| Serviço | Fornece |
|---|---|
| `RevenueMetrics` | faturamento, MRR, churn, LTV, upsell, receita por canal/campanha |
| `AdMetrics` | investido, CPL, CAC, ROAS |
| `CashFlow` | saldo, resultado do mês |
| `LeadFilters` | filtros reutilizáveis |
| `TrackingEvent` | eventos do funil por canal/campanha |

## Referência (crm-frontend)

`DashboardAdmin.tsx`: abas por rota (`/dashboard/:aba`), componentes `Card`, `ValorKpi`, `Barras`, `Split`, `LeadsPorEtapaBars`, `Fonte` (rodapé com a origem do dado), "número inventado nunca aparece".

## Riscos

- **Funil misturando unidades:** topo conta visitantes/sessões (eventos), base conta leads/clientes. Definir: visitas = sessões distintas com `page_view`; CTA/formulário = sessões distintas com o evento; lead = leads criados; conversa/proposta/cliente = leads que alcançaram a etapa (via `LeadActivity`) dentro do período de criação. Documentar no rodapé de cada bloco.
- **Leads sem tracking:** cadastro manual e visitantes com GPC não têm eventos; o funil de topo é amostra. Mostrar isso na fonte.
- **Consultas pesadas:** contar distintos por sessão em `tracking_events`; índices do REQ-003 cobrem `(type, created_at)`.
- **"O que quer destravar":** contagem de palavras exige stopwords pt-BR; manter lista curta interna.

## Direções de design

1. **`GrowthDashboard` orquestrando os serviços existentes + `FunnelMetrics` novo** (recomendada): nada recalculado em dois lugares.
2. Views SQL agregadas: rápidas, porém acopladas ao SQLite/MySQL e difíceis de testar.

## Impacto

Substitui o placeholder do REQ-001 em `crm.dashboard`; novos componentes Blade de KPI e gráfico.

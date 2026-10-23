# Análise — REQ-004 Leads e pipeline
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## Conceitos

- **Entidades:** `Lead` (REQ-002), `LeadStatus`, `LeadActivity` (linha do tempo), `LostReason`.
- **Operações:** listar/filtrar, mover etapa, marcar perdido, reabrir, anotar, definir próxima ação, cadastrar manual, exportar, apagar.

## O que existe

| Item | Estado |
|---|---|
| `Lead` | Criado no REQ-002 com `status` e origem |
| `TrackingEvent` | REQ-003, ligado por `visitor_id` |
| Layout/sidebar/page-header | REQ-001 |

## Referência (crm-frontend)

- `MarketingLeads.tsx`: barra da tela com busca e selects, números de resumo (Total, Convertidos, Com UTM), tabela com header sticky, UTM em chip mono, "—" para vazio, exportar CSV.
- `PipelineKanban.tsx`: colunas por etapa com contador, card com título/código, drawer de detalhe, busca no board.
- `LeadEntradaDossie.tsx`: dossiê com blocos de dados e linha do tempo.

## Riscos

- **Consistência da etapa:** mover por drag (fetch) e por formulário precisa passar pela mesma regra; centralizar num serviço.
- **N+1:** lista e kanban com contagem de atividades e última nota; usar `withCount`/`with` e paginação.
- **Kanban grande:** "Perdido" e "Fechado" acumulam; mostrar só os últimos 30 dias por padrão nessas colunas.
- **Regra de Fechado:** depende do REQ-005; até lá a coluna existe, mas só a venda move para ela.

## Direções de design

1. **Serviço `LeadPipeline` + `LeadActivity` polimórfica por tipo** (recomendada): uma porta de entrada para mudar etapa, histórico confiável.
2. Observers no model registrando mudanças: menos explícito e difícil de carregar motivo/usuário.

## Impacto

Enum `LeadStatus` completo, tabela `lead_activities`, colunas `next_action`, `next_action_at`, `lost_reason`, `lost_note`, `status_changed_at` em `leads`; telas e JS de kanban.

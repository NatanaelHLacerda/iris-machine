# Análise — REQ-006 Investimento em Meta Ads
**Status:** RASCUNHO

## Conceitos

- **Entidades:** `AdSpend` (dia × campanha × conjunto × anúncio), campanha (identificada pelo nome na UTM).
- **Operações:** lançar gasto, importar CSV, agregar por período/campanha, cruzar com leads/vendas, calcular CPL/CAC/ROAS, alertar break-even.

## O que existe

| Item | Estado |
|---|---|
| `Lead` | `channel=meta`, `utm_campaign/content/term` (REQ-002) |
| `Sale`/`Payment` | origem copiada do lead (REQ-005) |
| `RevenueMetrics` | receita por canal/campanha, LTV (REQ-005) |

## Referência (crm-frontend)

`MarketingTrafego.tsx`: gasto ÷ leads reais por campanha/conjunto/anúncio e período, "leads fora do Meta", Meta indisponível = "pendente — motivo", nunca zero.

## Riscos

- **Chave de cruzamento por nome:** renomear campanha no Meta quebra o vínculo histórico. Normalizar (trim, minúsculo) e permitir mapear apelidos depois; registrar no guia de UTM.
- **CSV do Gerenciador varia por idioma e colunas escolhidas:** mapear cabeçalhos pt-BR e en ("Nome da campanha"/"Campaign name", "Valor usado (BRL)"/"Amount spent (BRL)", "Dia"/"Day", "Impressões"/"Impressions", "Cliques no link"/"Link clicks"); recusar com mensagem clara quando faltar coluna obrigatória.
- **Duplicidade:** reimportar o mesmo período; usar chave única `(date, campaign, adset, ad)` com upsert.
- **Atribuição:** first-touch por UTM subestima (sem UTM, o lead cai em "direto"); mostrar "leads fora do Meta" para dar contexto.

## Direções de design

1. **Tabela `ad_spends` + importador CSV + lançamento manual** (recomendada até a `DP-0601` decidir pela API): zero credencial, funciona já.
2. Sincronização pela Marketing API: automática, mas exige app, token de longa duração, revisão do Meta e job agendado; melhor como REQ próprio.

## Impacto

Tabela, importador, serviço de métricas de ads, tela nova, item "Meta Ads" na sidebar.

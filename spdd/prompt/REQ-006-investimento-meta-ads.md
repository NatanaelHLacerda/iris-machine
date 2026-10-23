# REASONS — REQ-006 Investimento em Meta Ads
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## R — Requirements

**Problema:** sem custo, não há CPL, CAC nem ROAS.
**Critérios de sucesso:**
- [ ] `POST crm.ads.import` com CSV pt-BR ou en → upsert por `(date, campaign, adset, ad)`; resposta com linhas criadas/atualizadas/ignoradas.
- [ ] CSV sem coluna obrigatória → erro listando o que falta, nada gravado.
- [ ] `POST crm.ads.store` lança gasto manual.
- [ ] `AdMetrics::byCampaign($period)` retorna por campanha: spend, leads, customers, revenue, cpl, cac, roas, ltv_roas, is_unprofitable.
- [ ] Período sem gasto → métricas `null` ("—" na tela).

## E — Entities

- **AdSpend**: `date`, `platform` (`meta`), `campaign`, `adset` (nullable), `ad` (nullable), `campaign_key`/`adset_key`/`ad_key` (normalizados: trim + minúsculo), `spend_cents`, `impressions`, `link_clicks`, `source` (`csv`|`manual`), timestamps; único `(platform, date, campaign_key, adset_key, ad_key)`.
- Invariantes: `spend_cents ≥ 0`; `date` não futura.

## A — Approach

Importador `MetaAdsCsvImporter` com mapa de cabeçalhos pt-BR/en, valor BRL com vírgula convertido por `Money::toCents`, upsert em lote. `AdMetrics` cruza `ad_spends` com `leads`/`sales`/`payments` pelas chaves normalizadas de UTM. Tela no padrão "Tráfego" da referência.

## S — Structure

```text
app/Models/AdSpend.php
app/Support/Crm/{MetaAdsCsvImporter,AdMetrics,UtmKey}.php
app/Http/Controllers/Crm/{AdsController,AdSpendImportController,AdSpendController}.php
app/Http/Requests/Crm/{ImportAdSpendRequest,StoreAdSpendRequest}.php
database/migrations/*_create_ad_spends_table.php
database/factories/AdSpendFactory.php
resources/views/crm/ads/index.blade.php      resumo · tabela por campanha · tendência · guia de UTM
```

## O — Operations

1. `UtmKey::normalize(?string): ?string` (trim, minúsculo, espaços colapsados); usado também ao comparar `leads.utm_*`.
2. Migration/model/factory.
3. `MetaAdsCsvImporter::import(UploadedFile $file): ImportResult` (`created`, `updated`, `skipped`, `errors[]`):
   - detecta separador (`,` ou `;`) e BOM;
   - colunas obrigatórias: dia, nome da campanha, valor usado; opcionais: conjunto, anúncio, impressões, cliques no link;
   - linhas de total/sem data → `skipped`;
   - `AdSpend::upsert(...)` em lote de 500, `source=csv`.
4. `ImportAdSpendRequest`: `file` required|file|mimes:csv,txt|max:5120.
5. `StoreAdSpendRequest`: `date` required|date|before_or_equal:today, `campaign` required|max:200, `adset`/`ad` nullable, `spend` required (reais), `impressions`/`link_clicks` nullable|integer|min:0.
6. `AdMetrics` (período `CarbonPeriod`):
   - `summary($period): array{spend:?int, leads:int, customers:int, revenue:int, cpl:?int, cac:?int, roas:?float, ltv_roas:?float}`;
   - `byCampaign($period): Collection` (e `byAdset($campaignKey, $period)`);
   - `dailySpend($period): array<string,int>`;
   - `leadsOutsideMeta($period): int`;
   - `customers` = leads do Meta com primeira venda no período; `revenue` = pagamentos pagos no período de leads do Meta;
   - `is_unprofitable` = `cac !== null && cac > RevenueMetrics::ltvCents()`;
   - divisões por zero → `null`.
7. `AdsController::__invoke`: filtro de período (padrão: mês atual; atalhos 7d, 30d, mês, mês anterior, personalizado), resumo, tabela por campanha com linha expansível (`<details>`), gráfico de barras diário em SVG inline (sem lib), selo "No vermelho" com texto.
8. Card "Guia de UTM" com o padrão da `DP-0602` e botão copiar.

## N — Norms

Ver `spdd/README.md`. ROAS exibido como `2,4×`; CPL/CAC em reais; "—" para `null`.

## S — Safeguards

- Nunca exibir zero quando não houver gasto (`null` → "—").
- Importação inválida não grava nada (transação).
- Nenhum token ou credencial do Meta no front nem no repositório.
- Upload só CSV, até 5 MB, processado em memória sem salvar em `public`.

## Testes

Feature: importar CSV pt-BR e en, reimportar sem duplicar, CSV sem coluna → erro sem gravar, lançamento manual, tela com período vazio mostrando "—". Unit: `AdMetrics` (CPL/CAC/ROAS, divisão por zero, break-even), `UtmKey`, parser de valores BRL.

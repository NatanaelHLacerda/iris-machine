# REQ-006 — Investimento em Meta Ads
**Status:** RASCUNHO
**Origem:** conversa de 2026-10-23 (*"começarei investindo no Meta"*, *"mostre quanto de valor há investido em ads, retorno"*)

## Contexto

O tráfego pago começa pelo Meta (Facebook/Instagram). Os leads chegam com UTM (REQ-002) e as vendas herdam a origem (REQ-005). Falta o lado do custo.

## Problema

Sem o gasto por campanha não dá para calcular custo por lead, CAC nem ROAS, nem saber se o anúncio paga a si mesmo.

## Objetivo

Registrar o investimento no Meta por dia e campanha e cruzar com leads e receita.

## Atores

Admin.

## Regras de negócio

1. Gasto registrado por **dia × campanha** (e opcionalmente conjunto e anúncio), com impressões, cliques e valor gasto.
2. O cruzamento com leads e vendas usa `utm_campaign` (campanha), `utm_content` (conjunto) e `utm_term` (anúncio): o padrão de UTM do Meta deve ser configurado nos anúncios.
3. Métricas por período e por campanha:
   - **Investido** = soma do gasto;
   - **CPL** = investido ÷ leads do canal Meta;
   - **CAC** = investido ÷ clientes novos vindos do Meta;
   - **Receita atribuída** = pagamentos recebidos de leads do Meta;
   - **ROAS** = receita atribuída ÷ investido;
   - **ROAS projetado (LTV)** = clientes do Meta × LTV ÷ investido.
4. **Alerta de break-even**: CAC acima do LTV → campanha no vermelho.
5. Sem gasto registrado no período → métricas mostram "—", nunca zero.
6. Leads com campanha sem gasto registrado aparecem como "sem custo registrado".

## Escopo

### Incluído
- `/gestao-crm/meta-ads`: resumo do período, tabela por campanha (expansível por conjunto/anúncio), tendência diária.
- Lançamento manual de gasto.
- Importar o CSV exportado do Gerenciador de Anúncios.
- Guia na tela com o padrão de UTM a colar nos anúncios.

### Fora do escopo
- Criar ou editar campanhas pelo CRM.
- Conversions API / pixel (REQ futuro).

## Critérios de aceite

1. Importar o CSV do Gerenciador cria os gastos por dia e campanha; reimportar o mesmo período não duplica.
2. Tabela por campanha mostra investido, leads, CPL, clientes, CAC, receita e ROAS.
3. Campanha com CAC > LTV aparece sinalizada.
4. Período sem gasto mostra "—".

## Restrições

Sem dependência nova; token do Meta (se houver) só no `.env`, nunca no front.

## Dependências

REQ-001; REQ-002 (UTM); REQ-005 (receita, LTV).

## Decisões pendentes

| # | Pergunta | Recomendação |
|---|---|---|
| `DP-0601` | Origem do gasto: manual/CSV ou sincronização automática pela Marketing API do Meta? | Começar com CSV + manual; API como REQ próprio quando o investimento estabilizar (exige app no Meta, token de sistema e revisão) |
| `DP-0602` | Padrão de UTM dos anúncios | `utm_source=meta&utm_medium=paid&utm_campaign={{campaign.name}}&utm_content={{adset.name}}&utm_term={{ad.name}}` |

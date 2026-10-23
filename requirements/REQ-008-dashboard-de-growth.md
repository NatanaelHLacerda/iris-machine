# REQ-008 — Dashboard de growth
**Status:** RASCUNHO
**Origem:** conversa de 2026-10-23 (*"O crm irá me ajudar a gerir o crescimento da minha empresa"*, *"foque em growth"*, meta R$ 4.000/mês)

## Contexto

Todos os dados existem nos REQs 002–007. Falta a tela que responde em um olhar: estou crescendo? Vou bater a meta? Onde o funil perde gente? O anúncio se paga?

## Problema

As métricas ficam espalhadas; decidir onde investir tempo e dinheiro exige juntar tudo à mão.

## Objetivo

Tela inicial do CRM com meta, funil, aquisição, receita e saúde financeira, com abas no padrão da referência.

## Atores

Admin.

## Regras de negócio

1. **Aba Visão geral** (padrão):
   - **Meta do mês:** faturado × R$ 4.000, % atingido, quanto falta, ritmo diário necessário, projeção de fechamento (faturado + cobranças previstas do mês + média diária × dias restantes) e o status (no ritmo, atenção, fora do ritmo).
   - **KPIs:** faturado, MRR, alunos ativos, leads, conversão lead → cliente, investido em ads, ROAS, saldo em conta; cada um com a variação contra o período anterior.
   - **Ações do dia:** follow-ups atrasados, leads esfriando, cobranças atrasadas.
2. **Aba Funil:** visitas → clique em CTA → viu formulário → começou → lead → conversa → proposta → cliente, com a conversão de cada passo e o maior gargalo destacado; filtro por canal e campanha.
3. **Aba Aquisição:** leads, clientes e receita por canal e campanha; segmentos por momento (estudante, estagiário, júnior) e formato de interesse; principais "o que quer destravar" (palavras mais frequentes).
4. **Aba Receita:** faturamento por mês (12 meses), MRR, churn, LTV, upsell sessão → mentoria, ticket médio por formato.
5. Período global: mês atual (padrão), mês anterior, últimos 7/30/90 dias, personalizado.
6. Métrica sem base → "—" com dica do motivo.

## Escopo

### Incluído
- `/gestao-crm` (dashboard) com as quatro abas, cada uma com URL própria.
- Configuração da meta mensal (valor editável; padrão R$ 4.000).

### Fora do escopo
- Relatórios por e-mail, exportar PDF.
- Metas por canal.

## Critérios de aceite

1. Com o mês pela metade e R$ 2.000 faturados, a meta mostra 50%, falta R$ 2.000 e o ritmo diário necessário.
2. O funil mostra as oito etapas e destaca a de menor conversão.
3. Trocar o período atualiza todos os números e a comparação com o período anterior.
4. Sem gasto em ads, ROAS mostra "—".
5. Os cards de "Ações do dia" levam à lista filtrada correspondente.
6. A tela carrega em menos de 1s com 12 meses de dados de demonstração (REQ-009).

## Restrições

Sem biblioteca de gráficos (SVG/CSS); sem dependência nova.

## Dependências

REQ-003, 004, 005, 006, 007.

## Decisões pendentes

| # | Pergunta | Recomendação |
|---|---|---|
| `DP-0801` | Limiares do status da meta | No ritmo ≥ 100% da projeção; atenção 80–99%; fora < 80% |
| `DP-0802` | Cache das métricas | Sem cache até medir lentidão; depois cache de 5 min por período |

# Requisitos — Gestão CRM (iris-machine)

CRM de growth da Iris Machine, no mesmo projeto Laravel da landing, acessível em `/gestao-crm`.

## Convenção

```text
requirements/REQ-XXX-slug.md      ← semente (o quê e por quê)
spdd/analysis/REQ-XXX-slug.md     ← análise (o que existe, riscos, direções)
spdd/prompt/REQ-XXX-slug.md       ← REASONS Canvas (como; fonte da verdade do código)
```

## Cadeia e ordem de entrega

| REQ | Semente | Depende de |
|---|---|---|
| 001 | Acesso ao `/gestao-crm` (auth + casca) | — |
| 002 | Captura de leads da landing com origem | — |
| 003 | Tracking do funil da landing | 002 |
| 004 | Leads e pipeline | 001, 002 |
| 005 | Vendas e receita | 004 |
| 006 | Investimento em Meta Ads | 001 |
| 007 | Caixa | 005, 006 |
| 008 | Dashboard de growth | 003, 005, 006, 007 |
| 009 | Dados de demonstração | 002–007 |

## Template

```markdown
# REQ-XXX — Título
**Status:** RASCUNHO
**Origem:** conversa de AAAA-MM-DD

## Contexto
## Problema
## Objetivo
## Atores
## Regras de negócio
## Escopo
### Incluído
### Fora do escopo
## Critérios de aceite
## Restrições
## Dependências
## Decisões pendentes
```

## Status

`RASCUNHO` → `EM_REVISAO` → `APROVADO` (só por decisão humana) → `OBSOLETO`.

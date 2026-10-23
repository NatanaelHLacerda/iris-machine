# Análise — REQ-007 Caixa
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## Conceitos

- **Entidades:** `CashEntry` (lançamento manual), saldo inicial, extrato (visão unificada).
- **Operações:** lançar/editar/apagar manual, definir saldo inicial, compor extrato, calcular saldo/resultado/margem.

## O que existe

| Item | Estado |
|---|---|
| `Payment` (REQ-005) | `status=paid`, `paid_at`, `amount_cents`; `refunded` |
| `AdSpend` (REQ-006) | `date`, `spend_cents` |

## Riscos

- **Fonte duplicada:** copiar pagamentos e gastos para uma tabela de caixa cria duas verdades que divergem quando a origem muda. Melhor compor o extrato por consulta (`UNION`) das três fontes.
- **Estorno:** pagamento estornado deve sair do caixa na data do estorno; precisa de `refunded_at` em `payments` (ajuste no REQ-005).
- **Performance:** o saldo atual soma tudo desde o saldo inicial; volume baixo (um negócio de uma pessoa) torna isso barato; sem cache por ora.
- **Fuso:** agrupar por mês em `America/Sao_Paulo`.

## Direções de design

1. **Extrato composto por consulta (pagamentos + gastos + manuais)** (recomendada): uma só verdade por dado; caixa sempre coerente com vendas e ads.
2. Ledger materializado com observers: consultas mais simples, porém risco de divergência e mais código de sincronização.

## Impacto

Tabela `cash_entries`, chave de configuração do saldo inicial (tabela `crm_settings` chave/valor), coluna `refunded_at` em `payments`, tela nova.

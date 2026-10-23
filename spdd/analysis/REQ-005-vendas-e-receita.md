# Análise — REQ-005 Vendas e receita
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## Conceitos

- **Entidades:** `Sale`, `Subscription`, `Payment`, `Plan` (enum do REQ-002, preço em `config/iris.php`).
- **Operações:** registrar venda, gerar cobrança, marcar paga, estornar, cancelar assinatura, calcular faturamento/MRR/churn/upsell.

## O que existe

| Item | Estado |
|---|---|
| `config/iris.php` `plans` | `price` como texto (`'R$ 399,90'`, `'Sob consulta'`), usado na landing |
| `Lead` + `LeadPipeline` | REQ-002/004; `close()` reservado para a venda |
| Scheduler | `routes/console.php` disponível (Laravel 13), nada agendado |

## Riscos

- **Preço em texto:** o CRM precisa do valor numérico. Adicionar `price_cents` (e `billing: one_time|monthly`) em cada plano do config sem mudar o texto exibido na landing.
- **Cobranças duplicadas:** o comando mensal precisa ser idempotente (único por `subscription_id + due_date`).
- **Fuso:** "pago no mês" deve usar `America/Sao_Paulo`, não UTC (pagamento às 22h do dia 31 cairia no mês seguinte).
- **Atribuição:** receita deve ser rastreável até a campanha. Copiar `channel/utm_campaign` do lead na venda congela a atribuição mesmo se o lead for editado.

## Direções de design

1. **Venda → Assinatura → Pagamentos (cobranças)** (recomendada): suporta recorrência real, churn e caixa.
2. Só vendas com valor total: simples, mas não mede MRR, churn nem caixa mensal da mentoria.

## Impacto

Três tabelas, um comando agendado, tela de vendas, ação de venda no dossiê e no kanban, `config/iris.php` ganha `price_cents`/`billing`.

# REQ-007 — Caixa
**Status:** APROVADO — Natanael, 2026-10-23, segue a cadeia (*"aprovado, siga todas as cadeias de ponta a ponta automizadas."*): decisões pendentes na recomendação.
**Origem:** conversa de 2026-10-23 (*"quanto ainda resta em conta"* → opção 2: caixa da empresa)

## Contexto

O admin quer saber quanto sobrou de verdade: o que entrou (pagamentos de alunos) menos o que saiu (anúncios e outros custos).

## Problema

Faturamento e gasto com anúncio ficam em telas separadas; não há saldo nem visão de lucro do mês.

## Objetivo

Livro-caixa simples com saldo atual, entradas, saídas e resultado por mês.

## Atores

Admin.

## Regras de negócio

1. **Entradas automáticas:** cada pagamento marcado como pago (REQ-005) entra no caixa na data do pagamento; estorno sai.
2. **Saídas automáticas:** o gasto em anúncios (REQ-006) sai no caixa por dia.
3. **Lançamentos manuais:** outras entradas e saídas (ferramentas, hospedagem, assinatura do Claude, impostos, pró-labore) com categoria.
4. **Saldo inicial:** informado uma vez (com data); o saldo atual = saldo inicial + entradas − saídas desde então.
5. **Resultado do mês** = entradas − saídas do mês; **margem** = resultado ÷ entradas.
6. Lançamento automático não é editável no caixa (edita-se na origem).

## Escopo

### Incluído
- `/gestao-crm/caixa`: saldo atual, entradas, saídas, resultado e margem do mês; extrato filtrável; saídas por categoria; evolução do saldo.
- Lançar, editar e apagar lançamentos manuais.
- Definir o saldo inicial.

### Fora do escopo
- Conciliação bancária e integração com banco (Open Finance).
- Contas a pagar recorrentes (pode virar REQ).

## Critérios de aceite

1. Marcar uma cobrança como paga aparece como entrada no extrato e aumenta o saldo.
2. Importar gasto do Meta aparece como saída "Anúncios Meta".
3. Lançar uma saída manual com categoria reduz o saldo e aparece no gráfico por categoria.
4. Resultado do mês bate com entradas − saídas.
5. Tentar editar um lançamento automático pelo caixa não é permitido.

## Restrições

Dinheiro em centavos; sem dependência nova.

## Dependências

REQ-005 (pagamentos); REQ-006 (gasto em anúncios).

## Decisões pendentes

| # | Pergunta | Recomendação |
|---|---|---|
| `DP-0701` | Gasto do Meta sai do caixa no dia do gasto ou na data da cobrança do cartão? | No dia do gasto (simples e próximo do real para quem paga no cartão com limite) |
| `DP-0702` | Categorias de saída | Anúncios, Ferramentas, Infraestrutura, Impostos, Pró-labore, Outros |

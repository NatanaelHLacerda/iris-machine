# REQ-005 — Vendas e receita
**Status:** APROVADO — Natanael, 2026-10-23, segue a cadeia (*"aprovado, siga todas as cadeias de ponta a ponta automizadas."*): decisões pendentes na recomendação.
**Origem:** conversa de 2026-10-23 (*"minha meta é faturar 4mil reais por mês"*; planos da landing)

## Contexto

Produtos (`config/iris.php`):
- **Sessão avulsa:** R$ 129,90, pagamento único.
- **Mentoria individual:** R$ 399,90, encontros semanais, recorrente mensal.
- **Squad:** sob consulta, valor negociado por proposta.

Meta: **R$ 4.000/mês** (≈ 10 mentorias ativas).

## Problema

Sem registrar vendas e pagamentos, não há faturamento, receita recorrente, retenção nem retorno do investimento em anúncios.

## Objetivo

Registrar vendas a partir do lead, acompanhar mentorias ativas e cada pagamento recebido.

## Atores

Admin.

## Regras de negócio

1. Venda nasce de um lead e move o lead para **Fechado** (REQ-004).
2. Venda tem formato (sessão, mentoria, squad) e valor; o valor sugerido vem do plano, editável (desconto); squad sempre informado.
3. Sessão avulsa gera um pagamento único.
4. Mentoria e squad podem ser **recorrentes**: geram uma **assinatura** com valor mensal e dia de cobrança; a cada mês nasce uma cobrança prevista que o admin marca como paga.
5. Assinatura pode ser **cancelada** (com data e motivo); para de gerar cobranças.
6. **Faturamento do mês** = soma dos pagamentos com status pago e data de pagamento no mês.
7. **MRR** = soma do valor mensal das assinaturas ativas.
8. **Upsell**: lead que comprou sessão avulsa e depois mentoria conta como conversão de upsell.
9. Venda e pagamento herdam a origem (canal/campanha) do lead para atribuir receita ao anúncio.

## Escopo

### Incluído
- Registrar venda pelo dossiê do lead (e em "Fechar" no kanban).
- `/gestao-crm/vendas`: vendas do período, assinaturas ativas/canceladas, cobranças previstas, atrasadas e pagas.
- Marcar cobrança como paga (data, forma: Pix, cartão, boleto, outro); estornar.
- Cancelar assinatura.
- Comando agendado que gera as cobranças do mês.

### Fora do escopo
- Gateway de pagamento/checkout (Stripe, Mercado Pago).
- Emissão de nota fiscal.

## Critérios de aceite

1. Registrar mentoria para um lead cria venda, assinatura e a primeira cobrança; o lead vai para Fechado.
2. No mês seguinte o comando cria a nova cobrança da assinatura ativa.
3. Marcar como paga soma no faturamento do mês da data de pagamento.
4. Cancelar a assinatura reduz o MRR e não gera novas cobranças.
5. Cobrança vencida e não paga aparece como atrasada.
6. A venda mostra canal e campanha do lead.

## Restrições

Dinheiro em centavos; sem dependência nova.

## Dependências

REQ-004 (lead, pipeline, `LeadPipeline::close`).

## Decisões pendentes

| # | Pergunta | Recomendação |
|---|---|---|
| `DP-0501` | Meta mede faturamento recebido (caixa) ou vendido (competência)? | Recebido — é o que vira dinheiro em conta |
| `DP-0502` | Duração média da mentoria para LTV antes de haver histórico? | 3 meses, editável nas configurações |
| `DP-0503` | Squad é recorrente por padrão? | Não; escolher na venda |

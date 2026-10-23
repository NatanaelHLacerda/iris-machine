# REASONS — REQ-005 Vendas e receita
**Status:** RASCUNHO

## R — Requirements

**Problema:** sem faturamento, MRR nem retorno mensuráveis.
**Critérios de sucesso:**
- [ ] `POST crm.leads.sales.store` (mentoria, recorrente) → `Sale`, `Subscription(active)`, `Payment(pending, due=hoje)`; lead Fechado; atividade `SaleRecorded`.
- [ ] Sessão avulsa → `Sale` + `Payment` único (pode já nascer pago).
- [ ] Squad sem `amount` → 422.
- [ ] `crm:generate-charges` cria 1 cobrança por assinatura ativa no mês; rodar 2x não duplica.
- [ ] `PATCH crm.payments.pay` → `status=paid`, `paid_at`, `method`.
- [ ] `PATCH crm.payments.refund` → `status=refunded`.
- [ ] `PATCH crm.subscriptions.cancel` → `status=canceled`, `canceled_at`, `cancel_reason`.
- [ ] `RevenueMetrics::revenueForMonth` soma só `paid` com `paid_at` no mês em `America/Sao_Paulo`.

## E — Entities

- **Plan** (enum, REQ-002) + `config('iris.plans.*.price_cents')` (sessao 12990, mentoria 39990, squad null) e `billing` (one_time, monthly, custom).
- **Sale**: `lead_id`, `plan` (Plan), `amount_cents`, `is_recurring` (bool), `sold_at`, `channel`, `utm_campaign`, `utm_content`, `utm_term` (cópia do lead), `notes`.
- **Subscription**: `sale_id`, `lead_id`, `plan`, `monthly_amount_cents`, `billing_day` (1–28), `status` (enum `SubscriptionStatus`: Active, Canceled), `started_at`, `canceled_at`, `cancel_reason`.
- **Payment**: `sale_id`, `subscription_id` (nullable), `lead_id`, `amount_cents`, `due_date`, `status` (enum `PaymentStatus`: Pending, Paid, Refunded), `paid_at`, `method` (enum `PaymentMethod`: Pix, Cartao, Boleto, Outro); único `(subscription_id, due_date)`.
  - Derivado: `isOverdue()` = Pending e `due_date < hoje`.
- Invariantes: `amount_cents > 0`; assinatura cancelada não gera cobrança; `billing_day` ≤ 28 (evita meses curtos).

## A — Approach

Serviço `SaleRecorder` cria venda/assinatura/cobrança e fecha o lead em uma transação. `ChargeGenerator` idempotente rodando diariamente. Métricas em `RevenueMetrics` (consultas agregadas), consumidas pelo REQ-007/008.

## S — Structure

```text
config/iris.php                              + price_cents, billing por plano
app/Enums/{SubscriptionStatus,PaymentStatus,PaymentMethod}.php
app/Models/{Sale,Subscription,Payment}.php
app/Support/Crm/{SaleRecorder,ChargeGenerator,RevenueMetrics}.php
app/Console/Commands/GenerateChargesCommand.php   (crm:generate-charges)
routes/console.php                           Schedule::command('crm:generate-charges')->dailyAt('06:00')
app/Http/Controllers/Crm/{LeadSaleController,SaleController,PaymentController,SubscriptionController}.php
app/Http/Requests/Crm/{StoreSaleRequest,PayPaymentRequest,CancelSubscriptionRequest}.php
database/migrations/*_create_sales_table.php · *_create_subscriptions_table.php · *_create_payments_table.php
database/factories/{Sale,Subscription,Payment}Factory.php
resources/views/crm/sales/index.blade.php    abas: Vendas · Assinaturas · Cobranças
resources/views/crm/leads/partials/sale-form.blade.php
```

## O — Operations

1. Config e migrations; models com casts, relações (`Lead hasMany sales/payments`, `Sale hasOne subscription`, `hasMany payments`).
2. `StoreSaleRequest`: `plan` enum; `amount_cents` required_if plan=squad, inteiro > 0 (o form envia reais e o request converte); `is_recurring` boolean (forçado true para mentoria, false para sessão); `billing_day` 1–28; `sold_at` date; `paid_now` boolean; `method` enum required_if paid_now.
3. `SaleRecorder::record(Lead $lead, array $data): Sale` em transação: cria `Sale` (copiando a origem), se recorrente cria `Subscription` e a primeira `Payment` (due = `sold_at`), senão `Payment` única; `paid_now` → marca paga; `LeadPipeline::close($lead)`; atividade `SaleRecorded {sale_id, plan, amount}`.
4. `ChargeGenerator::run(CarbonImmutable $today): int`: para cada assinatura ativa, se `billing_day` ≤ dia de hoje e não há cobrança com `due_date` no mês, cria. Retorna o total criado.
5. `PaymentController::pay/refund`; `SubscriptionController::cancel`.
6. `RevenueMetrics`:
   - `revenueForMonth(CarbonImmutable $month): int`;
   - `revenueByChannel(CarbonPeriod): array<string,int>` e `revenueByCampaign(CarbonPeriod): array<string,int>`;
   - `mrr(): int`; `activeSubscriptions(): int`;
   - `churnRate(CarbonImmutable $month): ?float` (canceladas no mês ÷ ativas no início; null sem base);
   - `averageLifetimeMonths(): ?float` (média de meses das assinaturas encerradas; null se < 3 encerradas);
   - `ltvCents(): int` = mensalidade média × (`averageLifetimeMonths` ?? `config('iris.crm.default_lifetime_months')`);
   - `upsellCount(CarbonPeriod): int` (leads com sessão anterior a uma mentoria).
7. Tela `/gestao-crm/vendas`: números de resumo (faturado no mês, MRR, assinaturas ativas, cobranças atrasadas); abas com tabelas; ações pagar/estornar/cancelar com `<dialog>` de confirmação.
8. Dossiê do lead: bloco "Vendas" com histórico e botão "Registrar venda" (form com valor pré-preenchido pelo plano de interesse).
9. Kanban: soltar em Fechado abre o form de venda em vez de mover direto.

## N — Norms

Ver `spdd/README.md`. Conversão reais ↔ centavos só em `App\Support\Crm\Money` (`toCents(string): int`, `format(int): string`).

## S — Safeguards

- Nenhum float para dinheiro.
- Cobrança nunca duplicada para o mesmo mês da assinatura.
- Pagamento estornado nunca soma faturamento.
- Fechado sempre com venda (`LeadPipeline::close` só chamado pelo `SaleRecorder`).

## Testes

Feature: registrar sessão/mentoria/squad, squad sem valor 422, pagar/estornar, cancelar assinatura, tela de vendas com resumo. Unit: `ChargeGenerator` (idempotência, billing_day, cancelada), `RevenueMetrics` (fuso no fim do mês, MRR, churn, LTV com fallback, upsell), `Money`.

# REASONS — REQ-007 Caixa
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## R — Requirements

**Problema:** não se sabe quanto resta em conta nem o resultado do mês.
**Critérios de sucesso:**
- [ ] `CashFlow::balance()` = saldo inicial + pagamentos pagos − estornos − gastos de ads + manuais (entradas − saídas) desde a data do saldo inicial.
- [ ] `CashFlow::statement($period)` lista as três fontes ordenadas por data, com `origin` (`payment|refund|ad_spend|manual`) e `editable` só para `manual`.
- [ ] `CashFlow::monthSummary($month)` → entradas, saídas, resultado, margem (`null` sem entradas), saídas por categoria.
- [ ] CRUD de `CashEntry` manual; `PATCH`/`DELETE` em lançamento automático não existe (rota só para manual).
- [ ] Sem saldo inicial definido → tela pede para definir e mostra saldo "—".

## E — Entities

- **CashEntry**: `date`, `direction` (enum `CashDirection`: In, Out), `category` (enum `CashCategory`: Anuncios, Ferramentas, Infraestrutura, Impostos, ProLabore, Outros, OutrasEntradas), `description`, `amount_cents` (> 0), timestamps.
- **Setting** (`crm_settings`): `key` único, `value` json. Chaves: `opening_balance` `{amount_cents, date}`.
- **Payment** (+ `refunded_at`, REQ-005 ajustado).
- Invariantes: `amount_cents > 0`, sinal dado por `direction`; gasto de ads sempre categoria Anuncios no extrato.

## A — Approach

`App\Support\Crm\CashFlow` compõe o extrato via três consultas agregadas/mescladas em PHP (volume baixo) ou `unionAll` do query builder, sempre filtradas a partir da data do saldo inicial. Nada é copiado das fontes.

## S — Structure

```text
app/Enums/{CashDirection,CashCategory}.php
app/Models/{CashEntry,Setting}.php
app/Support/Crm/CashFlow.php
app/Http/Controllers/Crm/{CashController,CashEntryController,OpeningBalanceController}.php
app/Http/Requests/Crm/{StoreCashEntryRequest,UpdateOpeningBalanceRequest}.php
database/migrations/*_create_cash_entries_table.php · *_create_crm_settings_table.php · *_add_refunded_at_to_payments_table.php
database/factories/CashEntryFactory.php
resources/views/crm/cash/index.blade.php
```

## O — Operations

1. Migrations/models/factory; `Setting::get(string $key, mixed $default = null)` / `Setting::put(string $key, mixed $value)`.
2. `PaymentController::refund` (REQ-005) passa a gravar `refunded_at`.
3. `CashFlow`:
   - `openingBalance(): ?array{amount_cents:int, date:CarbonImmutable}`;
   - `balance(): ?int` (null sem saldo inicial);
   - `statement(CarbonPeriod $period): Collection<StatementLine{date, origin, category, description, amount_cents (com sinal), editable, link}>` — pagamento: "Pagamento — {lead} · {plano}" com link ao lead; ads: agregado por dia "Anúncios Meta" com link ao `crm.ads`;
   - `monthSummary(CarbonImmutable $month): array{in:int, out:int, result:int, margin:?float, out_by_category: array<string,int>}`;
   - `balanceSeries(CarbonPeriod $period): array<string,int>` (saldo ao fim de cada dia).
4. `CashController::__invoke`: período (padrão mês atual), números de resumo (Saldo em conta, Entradas, Saídas, Resultado, Margem), extrato com filtro de origem/categoria, barras de saídas por categoria e linha do saldo em SVG inline.
5. `CashEntryController` store/update/destroy (só manuais), com `<dialog>` de lançamento.
6. `OpeningBalanceController::update`: valor e data.

## N — Norms

Ver `spdd/README.md`. Entradas em verde-neutro com sinal "+", saídas com "−"; resultado negativo com texto "prejuízo", nunca só cor.

## S — Safeguards

- Nenhuma cópia de pagamentos ou gastos para `cash_entries`.
- Lançamento automático nunca editável pelo caixa.
- Saldo nunca exibido como zero sem saldo inicial ("—" + chamada para definir).
- Sem float em dinheiro.

## Testes

Feature: saldo com as três fontes, estorno reduz o saldo, extrato com `editable` correto, CRUD manual, sem saldo inicial → "—", rota de edição inexistente para automáticos. Unit: `CashFlow` (resumo do mês, margem `null`, fuso no fim do mês, série de saldo).

## Sincronização com o código (2026-10-23)

- `CashCategory` ganhou `Alunos` (pagamentos no extrato), proibida em lançamento manual.
- `CashFlow::summary(period)` serve qualquer período; `monthSummary` delega para ele. O model de configuração é `Setting` (tabela `crm_settings`).

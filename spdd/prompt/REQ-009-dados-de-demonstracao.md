# REASONS — REQ-009 Dados de demonstração
**Status:** RASCUNHO

## R — Requirements

**Problema:** CRM vazio antes da operação real.
**Critérios de sucesso:**
- [ ] `crm:demo --fresh` → `migrate:fresh` + cenário; imprime e-mail e senha do admin de demo.
- [ ] `APP_ENV=production` → falha com mensagem, nada alterado.
- [ ] Sem `--fresh` e já houver leads → avisa e sai com `FAILURE`.
- [ ] Funil coerente em todos os meses: visitas > CTA > form_view > form_start > leads > conversas > propostas > clientes.
- [ ] Último mês: faturamento entre R$ 3.000 e R$ 4.500; ao menos 1 campanha com `is_unprofitable`.
- [ ] Mesma saída a cada execução (seed fixa).

## E — Entities

Todas dos REQs 002–007. Parâmetros do cenário (`DemoScenario::MONTHS`), um por mês (6 meses até hoje):
- `daily_visits` (40 → 180), `meta_share` (0.55 → 0.7), `cta_rate` (~0.22), `form_view_rate` (~0.6), `form_start_rate` (~0.45), `lead_rate` (~0.55), `contact_rate` 0.85, `meeting_rate` 0.55, `proposal_rate` 0.7, `close_rate` 0.4;
- campanhas: `vibe-coding-junior` (boa), `portfolio-recrutador` (média), `claude-code-tcc` (no vermelho), `remarketing-lp` (barata), com gasto diário e peso de leads.

## A — Approach

Simulação diária determinística: para cada dia gera sessões e eventos, converte uma fração em leads com origem coerente, avança leads pelo pipeline com datas futuras plausíveis (gravando `LeadActivity`), fecha vendas pelo `SaleRecorder`, roda o `ChargeGenerator` mês a mês, marca pagamentos (90% pagos, alguns atrasados), cancela assinaturas pela taxa de churn, grava `AdSpend` diário e lançamentos manuais de caixa (ferramentas e infraestrutura mensais). Inserts em lote para eventos.

## S — Structure

```text
database/seeders/DemoSeeder.php
database/seeders/Demo/DemoScenario.php
app/Console/Commands/DemoCommand.php      (crm:demo {--fresh})
```

## O — Operations

1. `DemoCommand::handle(): int`: recusa produção (`app()->isProduction()`); `--fresh` → `migrate:fresh`; sem `--fresh` com leads existentes → aviso + `FAILURE`; `fake()->seed(2026)`, `mt_srand(2026)`; chama `DemoSeeder`; imprime credenciais.
2. `DemoSeeder::run()`: guarda de produção; cria admin `admin@iris.test` com `Str::password(16)` (exposta ao comando); `Setting::put('opening_balance', {amount_cents: 150000, date: início})`; `Setting::put('monthly_goal_cents', 400000)`; `(new DemoScenario)->run()`.
3. `DemoScenario::run()`: loop dia a dia do início até hoje:
   - sessões do dia por `daily_visits ± ruído`; canal por `meta_share` (resto: orgânico, direto, google); campanha por peso; eventos em buffer, `TrackingEvent::insert` a cada 1.000;
   - leads das sessões que chegam a `form_submit`, via `LeadFactory` com `visitor_id`, UTM e `created_at` do dia; `level`/`plan_interest` com pesos (júnior e estudante maioria; mentoria maioria);
   - progressão: para cada lead, sorteia até onde chega, com 1–5 dias entre etapas, via `LeadPipeline::move` com `Carbon::setTestNow` no instante da etapa (restaurado ao fim); perdidos com motivo;
   - fechamento: `SaleRecorder::record` com plano final (mentoria 70%, sessão 25%, squad 5% com valor entre R$ 900 e R$ 1.800); 30% das sessões viram mentoria depois de 10–30 dias;
   - no 1º dia de cada mês: `ChargeGenerator::run`, churn das assinaturas ativas, lançamentos manuais (Claude R$ 110, hospedagem R$ 60, domínio/ferramentas);
   - pagamentos: 90% pagos em 0–3 dias do vencimento, resto pendente (vira atrasado);
   - `AdSpend` diário por campanha (`source=manual`).
4. Estado de "hoje": garantir 3–5 leads em Novo há > 24h, 3–6 follow-ups vencidos, 1–3 cobranças atrasadas.
5. Desligar `Model::withoutEvents` onde não houver efeito necessário e envolver cada mês em transação.

## N — Norms

Ver `spdd/README.md`. Dados fictícios em pt-BR (`fake('pt_BR')`), e-mails `@example.com`.

## S — Safeguards

- Nunca rodar em produção (comando e seeder checam).
- Nunca usar dados pessoais reais.
- `DatabaseSeeder` não chama o `DemoSeeder` (só o comando).
- O cenário usa os serviços reais (`LeadPipeline`, `SaleRecorder`, `ChargeGenerator`), nunca grava status/venda por fora.

## Testes

Feature `DemoCommandTest`: recusa produção; popula com funil coerente e faturamento do último mês na faixa; sem `--fresh` com dados → `FAILURE`. Com `daily_visits` reduzido por parâmetro de teste para rodar rápido.

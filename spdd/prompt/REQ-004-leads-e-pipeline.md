# REASONS — REQ-004 Leads e pipeline
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## R — Requirements

**Problema:** leads esfriam sem follow-up; não se vê onde a venda trava.
**Critérios de sucesso:**
- [ ] `GET crm.leads.index` lista paginado (50), filtra por `status, channel, utm_campaign, level, plan_interest, from, to, q`.
- [ ] `GET crm.pipeline` mostra colunas na ordem do enum, com contadores.
- [ ] `PATCH crm.leads.status` muda etapa e cria `LeadActivity(type=status_changed, from, to)`.
- [ ] `status=perdido` sem `lost_reason` → 422.
- [ ] `status=fechado` por esta rota → 422 ("registre a venda").
- [ ] `POST crm.leads.notes` cria `LeadActivity(type=note)`.
- [ ] `PATCH crm.leads.next-action` grava `next_action` e `next_action_at`.
- [ ] `GET crm.leads.export` baixa CSV com o mesmo filtro.
- [ ] `DELETE crm.leads.destroy` apaga lead, envios, atividades e eventos do `visitor_id`.

## E — Entities

- **LeadStatus** (enum, ordenado): Novo, ContatoFeito, ConversaAgendada, DiagnosticoFeito, Proposta, Fechado, Perdido; `label()`, `isOpen()`, `order()`.
- **LostReason** (enum): SemResposta, Preco, Timing, ForaDoPublico, EscolheuOutro, Outro.
- **Lead** (+ colunas): `status_changed_at`, `next_action` (string 160, nullable), `next_action_at` (datetime, nullable), `lost_reason` (nullable), `lost_note` (nullable), `meeting_at` (nullable, preenchido ao ir para ConversaAgendada).
  - Derivados: `isOverdue()` (next_action_at < now), `isCooling()` (Novo e created_at < now-24h).
- **LeadActivity**: `lead_id`, `type` (enum `ActivityType`: StatusChanged, Note, NextActionSet, SaleRecorded, Created), `data` (json), `user_id` (nullable), `created_at`.
- Invariantes: `lost_reason` presente sse status Perdido; Fechado só via REQ-005.

## A — Approach

`App\Support\Crm\LeadPipeline` concentra mudança de etapa (validação de regra + histórico, em transação). Telas Blade server-rendered; kanban com drag-and-drop HTML5 em `crm.js` chamando `PATCH` via `fetch` e revertendo o card em erro. Filtros em `App\Support\Crm\LeadFilters` reutilizados por lista, export e dashboard.

## S — Structure

```text
app/Enums/{LeadStatus,LostReason,ActivityType}.php
app/Models/LeadActivity.php
app/Support/Crm/{LeadPipeline,LeadFilters}.php
app/Http/Controllers/Crm/{LeadController,PipelineController,LeadStatusController,LeadNoteController,LeadNextActionController,LeadExportController}.php
app/Http/Requests/Crm/{StoreLeadRequest,UpdateLeadStatusRequest,StoreLeadNoteRequest,UpdateNextActionRequest}.php
database/migrations/*_add_pipeline_columns_to_leads_table.php · *_create_lead_activities_table.php
resources/views/crm/leads/{index,show,create}.blade.php
resources/views/crm/pipeline/index.blade.php
resources/views/crm/components/{status-badge,channel-chip,utm-chip,money}.blade.php
resources/js/crm.js                          kanban + confirmação de exclusão
```

## O — Operations

1. Migrations: colunas novas em `leads` + índice `(status, created_at)`; `lead_activities` com índice `(lead_id, created_at)`.
2. `LeadFilters::apply(Builder $query, array $filters): Builder` e `fromRequest(Request): array` (aceita os filtros do R; `q` busca em name/email/whatsapp com `like`).
3. `LeadPipeline::move(Lead $lead, LeadStatus $to, ?LostReason $reason = null, ?string $note = null, ?CarbonImmutable $meetingAt = null): Lead`:
   - `$to === Fechado` → `DomainException`;
   - `$to === Perdido` exige `$reason`; saindo de Perdido limpa `lost_*`;
   - `ConversaAgendada` grava `meeting_at` quando informado;
   - atualiza `status`, `status_changed_at`, cria atividade `StatusChanged {from,to,reason,note}`; tudo em `DB::transaction`.
   - `LeadPipeline::close(Lead $lead): void` (usado só pelo REQ-005).
4. `LeadController`: `index` (filtros, paginação, resumo: total, por etapa aberta, fechados, perdidos, % com UTM), `show` (lead + submissions + activities + eventos por `visitor_id`, ordenados numa timeline única), `create`/`store` (cadastro manual, `channel` escolhido, `Created` activity), `destroy`.
5. `PipelineController::__invoke`: leads agrupados por status; Fechado e Perdido limitados aos últimos 30 dias; cards com nome, formato de interesse, canal, dias na etapa, selo atrasado/esfriando.
6. `LeadStatusController::update` (JSON e form): chama `LeadPipeline::move`; `DomainException` → 422.
7. `LeadNoteController::store`, `LeadNextActionController::update`.
8. `LeadExportController`: `streamDownload` CSV (`;`, UTF-8 BOM) com colunas: data, nome, e-mail, WhatsApp, momento, formato, etapa, canal, campanha, conjunto, anúncio.
9. Views seguindo a referência: page-header com busca + selects; números de resumo; tabela com header sticky; chip mono para UTM; badges de etapa com texto (nunca só cor); dossiê com colunas "Dados / Origem" e "Linha do tempo"; botão WhatsApp `https://wa.me/55{whatsapp}`.
10. `crm.js` kanban: `draggable` nos cards, `dragover/drop` nas colunas, `fetch PATCH` com CSRF; em Perdido abre `<dialog>` pedindo motivo; erro → devolve o card e mostra toast.

## N — Norms

Ver `spdd/README.md`. Rotas: `crm.leads.*`, `crm.pipeline`. Atividades sempre com `user_id` do admin logado.

## S — Safeguards

- Toda mudança de etapa passa por `LeadPipeline` (nunca `update(['status'])` solto).
- Fechado nunca sem venda.
- Busca com binding (sem SQL cru interpolado).
- Exclusão sempre com confirmação e só via `DELETE`.
- Estado nunca só por cor: badge sempre com texto.

## Testes

Feature: lista + filtros + resumo, mover etapa com histórico, Perdido sem motivo 422, Fechado recusado, reabrir Perdido, nota, próxima ação, export CSV com filtro, exclusão em cascata, acesso sem login → login. Unit: `LeadPipeline` (regras), `LeadFilters`.

## Sincronização com o código (2026-10-23)

- Novas classes de apoio: `App\Support\Crm\Period` (períodos no fuso de São Paulo, usado por todas as telas) e `LeadTimeline` (une atividades, envios e eventos `page_view`/`cta_click`).
- Filtro `flag=atrasados|esfriando` e escopos `Lead::overdue()` / `Lead::cooling()` (atalhos do dashboard).
- Soltar um card em Fechado abre o dossiê com o diálogo de venda (`#venda`).

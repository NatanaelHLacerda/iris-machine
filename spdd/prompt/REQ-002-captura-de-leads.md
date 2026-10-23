# REASONS — REQ-002 Captura de leads
**Status:** RASCUNHO

## R — Requirements

**Problema:** leads da landing se perdem e sem origem.
**Critérios de sucesso:**
- [ ] `POST /leads` válido (JSON) → 201 `{message}`; cria `Lead` (`status=novo`) e `LeadSubmission`.
- [ ] Sem JS: `POST /leads` → redirect para `/#agendar` com flash de sucesso.
- [ ] Inválido → 422 com erros por campo.
- [ ] `site` preenchido → 201 e nada gravado.
- [ ] Mesmo e-mail (case-insensitive) → reaproveita o `Lead`, cria nova `LeadSubmission`, atualiza `last_submitted_at`.
- [ ] `utm_source=facebook` ou `fbclid` presente → `channel=meta`.
- [ ] 6º envio do mesmo IP em 60s → 429.

## E — Entities

- **Lead**
  - `id`, `name`, `email` (único, minúsculo), `whatsapp` (só dígitos, nullable), `level` (enum `LeadLevel`), `plan_interest` (enum `Plan`, nullable), `goal` (text, nullable)
  - `status` (enum `LeadStatus`, default `novo`; transições no REQ-004)
  - origem first-touch: `channel` (enum `Channel`), `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`, `fbclid`, `referrer`, `landing_path` (todos nullable)
  - `visitor_id` (uuid, nullable, liga ao REQ-003)
  - `last_submitted_at`, timestamps
  - Invariante: `email` único; origem nunca sobrescrita por envio posterior.
- **LeadSubmission**: `lead_id`, `payload` (json com os campos enviados), mesma origem do envio, `ip_hash` (sha256 com `app.key`), `user_agent`, `created_at`.
- **Enums** (`app/Enums`): `LeadLevel` (Estudante, Estagiario, Junior, Outro), `Plan` (Sessao, Mentoria, Squad), `Channel` (Meta, Google, Organico, Direto, Outro), `LeadStatus` (definido no REQ-004; este REQ usa só `Novo`).

## A — Approach

Controller único `StoreLeadController` com Form Request; classificação de canal em `App\Support\Crm\ChannelClassifier`; front captura a origem no primeiro acesso e envia em campos ocultos; envio por `fetch` com fallback de POST.

## S — Structure

```text
routes/web.php                              POST /leads (name: leads.store, throttle:leads)
app/Http/Controllers/StoreLeadController.php
app/Http/Requests/StoreLeadRequest.php
app/Models/Lead.php · app/Models/LeadSubmission.php
app/Enums/{LeadLevel,Plan,Channel,LeadStatus}.php
app/Support/Crm/ChannelClassifier.php
database/migrations/*_create_leads_table.php · *_create_lead_submissions_table.php
database/factories/{Lead,LeadSubmission}Factory.php
resources/views/partials/lead.blade.php     action + hidden de origem
resources/js/app.js                         initAttribution + initLeadForm
```

## O — Operations

1. Migrations e models com casts para enums, `payload` em array e datetimes; `Lead hasMany submissions`.
2. Enums com `label(): string`. `LeadLevel`/`Plan` com valores iguais às chaves de `config/iris.php`.
3. `StoreLeadRequest::rules()`:
   - `name` required|string|max:120; `email` required|email|max:160; `whatsapp` nullable|string|max:30;
   - `level` required + `Rule::enum(LeadLevel)`; `plan` nullable + `Rule::enum(Plan)`; `goal` nullable|string|max:1000;
   - `site` nullable; `utm_*`, `fbclid`, `referrer`, `landing_path` nullable|string|max:255; `visitor_id` nullable|uuid.
   - `prepareForValidation()`: `email` minúsculo e trim; `whatsapp` só dígitos.
4. `ChannelClassifier::classify(?string $utmSource, ?string $fbclid, ?string $referrer): Channel` com as regras do REQ (buscadores: google, bing, duckduckgo, yahoo).
5. `StoreLeadController::__invoke(StoreLeadRequest): JsonResponse|RedirectResponse`:
   - honeypot → sucesso sem gravar;
   - `DB::transaction`: `Lead::firstOrCreate(['email'], [...dados + origem + channel])`; se existia, `update` só dos dados de contato não vazios + `last_submitted_at`; `submissions()->create(...)`;
   - `expectsJson()` → 201 `{message: 'Recebemos seu contato! Vamos te chamar em breve.'}`; senão redirect `/#agendar` com `lead_sent`.
6. Rate limiter `leads` em `AppServiceProvider`: 5/min por IP.
7. `lead.blade.php`: `action="{{ route('leads.store') }}"`, `@csrf`, hidden `utm_source…landing_path`, `visitor_id`; aviso de sucesso via `session('lead_sent')`.
8. `app.js`:
   - `initAttribution()`: no carregamento, se houver UTM/fbclid na URL ou não houver first-touch salvo, gravar `{utm_*, fbclid, referrer: document.referrer externo, landing_path, at}` em `localStorage['iris_ft']` (expira em 30 dias; try/catch); preencher os hidden.
   - `initLeadForm()`: `fetch` POST com `FormData`, header `Accept: application/json` + `X-CSRF-TOKEN`; 201 → mostra `data-lead-success`; 422 → preenche `data-error`; outro → `data-error="form"` genérico; botão desabilitado durante o envio.

## N — Norms

Ver `spdd/README.md`. Mensagens de erro em pt-BR (`lang` ou `messages()` no request).

## S — Safeguards

- Nunca guardar IP em texto puro.
- Nunca sobrescrever a origem first-touch de um lead existente.
- Nunca revelar ao visitante que o e-mail já existia.
- Não quebrar a animação nem o layout da landing (`HomeTest` continua verde).

## Testes

Feature `StoreLeadTest`: cria lead + submission, 422 por campo, honeypot, dedupe por e-mail com origem preservada, canal `meta` por utm e por fbclid, throttle 429, fallback não-JSON. Unit `ChannelClassifierTest`: tabela de casos.

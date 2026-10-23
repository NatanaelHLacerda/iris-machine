# REASONS — REQ-003 Tracking do funil
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## R — Requirements

**Problema:** topo e meio do funil invisíveis.
**Critérios de sucesso:**
- [ ] `POST /e` com lote válido → 204 e um registro por evento.
- [ ] `type` fora da lista → evento descartado, demais gravados.
- [ ] Lote com mais de 20 eventos → 422.
- [ ] User-agent de bot → 204 sem gravar.
- [ ] `Origin` diferente de `config('app.url')` → 403.
- [ ] Lead do REQ-002 com `visitor_id` liga aos eventos do mesmo visitante.

## E — Entities

- **TrackingEvent**: `id`, `type` (enum `EventType`: PageView, CtaClick, FormView, FormStart, FormSubmit), `target` (nullable, ex.: `hero`, `nav`, `cta-bar`, `plan:mentoria`), `visitor_id` (uuid), `session_id` (uuid), `path`, `channel` (enum `Channel`), `utm_source`, `utm_campaign`, `utm_content`, `utm_term` (nullable), `ip_hash`, `created_at` (sem `updated_at`).
- Índices: `(type, created_at)`, `(visitor_id)`, `(utm_campaign)`.
- Invariante: `form_view`/`form_start` únicos por `session_id` (garantido no front; tolerado no back).

## A — Approach

Endpoint de primeira parte com `navigator.sendBeacon` em lote, gravação síncrona por `insert` em massa, sem fila. Exceção de CSRF compensada por checagem de `Origin` e throttle.

## S — Structure

```text
routes/web.php                               POST /e (name: tracking.store, throttle:tracking)
app/Http/Controllers/StoreTrackingEventsController.php
app/Http/Requests/StoreTrackingEventsRequest.php
app/Models/TrackingEvent.php · app/Enums/EventType.php
app/Support/Crm/BotDetector.php
database/migrations/*_create_tracking_events_table.php
database/factories/TrackingEventFactory.php
bootstrap/app.php                            validateCsrfTokens(except: ['e'])
resources/js/tracking.js                     importado por app.js
resources/views/partials/*.blade.php         data-track="hero|nav|cta-bar" nos CTAs
```

## O — Operations

1. Migration e model (`$timestamps = false`, `created_at` preenchido no insert, `UPDATED_AT = null`).
2. `StoreTrackingEventsRequest`: `visitor_id` required|uuid, `session_id` required|uuid, `events` required|array|max:20, `events.*.type` required|string, `events.*.target` nullable|string|max:60, `events.*.path` required|string|max:255, `attribution` array nullable (`utm_*`, `fbclid`, `referrer`). `authorize()`: `Origin`/`Referer` com o host de `app.url`, senão 403.
3. `BotDetector::isBot(?string $userAgent): bool` (vazio, `bot|crawl|spider|slurp|headless|lighthouse|preview`).
4. `StoreTrackingEventsController::__invoke(): Response`: bot → 204; filtra `type` válidos via `EventType::tryFrom`; `channel` via `ChannelClassifier` (REQ-002); `TrackingEvent::insert($rows)`; 204.
5. Rate limiter `tracking`: 60/min por IP.
6. `resources/js/tracking.js`:
   - `shouldTrack()`: false se `navigator.globalPrivacyControl` ou `doNotTrack === '1'`.
   - `visitorId()` (`localStorage['iris_vid']`) e `sessionId()` (`sessionStorage['iris_sid']`), `crypto.randomUUID()`, try/catch com fallback em memória.
   - fila em memória; `flush()` com `sendBeacon('/e', Blob JSON)` em `visibilitychange=hidden`, `pagehide` e a cada 5s se houver eventos.
   - `page_view` no load; `cta_click` em `[data-track]` e `[data-plan]` (`plan:<slug>`); `form_view` com `IntersectionObserver` no `#agendar` (1x/sessão); `form_start` no primeiro `focusin` do form (1x/sessão); `form_submit` exportado e chamado pelo `initLeadForm` no 201.
   - Exporta `getVisitorId()` para o REQ-002 preencher o hidden `visitor_id`.
7. Partials: `data-track` nos CTAs `#agendar` do hero, da nav e da cta-bar.

## N — Norms

Ver `spdd/README.md`. JS sem dependência nova, módulo ES.

## S — Safeguards

- Nenhum dado pessoal (nome, e-mail, telefone) em evento.
- IP só como hash.
- Falha do tracking nunca pode quebrar a landing nem o envio do formulário (try/catch, sem `await`).
- Não remover CSRF de nenhuma outra rota.

## Testes

Feature `StoreTrackingEventsTest`: 204 e grava, tipo inválido descartado, lote > 20 → 422, bot → nada gravado, origin errado → 403, canal derivado da UTM. Unit `BotDetectorTest`.

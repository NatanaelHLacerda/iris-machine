# Análise — REQ-003 Tracking do funil
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## Conceitos

- **Entidades:** `TrackingEvent`, visitante (`visitor_id`), sessão (`session_id`).
- **Operações:** emitir evento, ingerir em lote, filtrar bots, deduplicar por sessão, ligar ao lead.

## O que existe

| Item | Estado |
|---|---|
| CTAs | `href="#agendar"` em `hero`, `nav`, `cta-bar` e `plans` (`data-plan`) |
| Formulário | `section#agendar`, `form[data-lead-form]` |
| `app.js` | GSAP/ScrollTrigger já carregados; `initPlanLinks` escuta `[data-plan]` |
| Fila | `QUEUE_CONNECTION=sync`; tabela `jobs` já migrada |
| Banco | SQLite local |

## Riscos

- **Volume:** um evento por ação; com tráfego pago o crescimento é rápido. Inserir em lote e indexar `(type, created_at)` e `(visitor_id)`.
- **Latência:** gravar direto na requisição é barato (insert simples); fila só se o volume pedir. `sendBeacon` não espera resposta.
- **Abuso:** endpoint público. Throttle por IP, limite de eventos por lote (20), validação estrita de `type`.
- **Precisão:** bloqueadores de anúncio podem barrar `/t`, então usar um caminho neutro (`/e`), mesma origem.
- **Privacidade:** sem dado pessoal no evento; o `visitor_id` só vira pessoa quando ela mesma envia o formulário.

## Direções de design

1. **Tabela única `tracking_events` + endpoint de lote com `sendBeacon`** (recomendada): simples e suficiente para o funil.
2. Agregados diários pré-calculados: mais rápido para o dashboard, mas prematuro; pode entrar depois como cache.

## Impacto

Migration, model, controller e rotas novas; `app.js` ganha `initTracking`; `CSRF` precisa de exceção para o endpoint (beacon não envia token), mitigada por validação de `Origin`.

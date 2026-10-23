# REQ-003 — Tracking do funil da landing
**Status:** APROVADO — Natanael, 2026-10-23, segue a cadeia (*"aprovado, siga todas as cadeias de ponta a ponta automizadas."*): decisões pendentes na recomendação.
**Origem:** conversa de 2026-10-23 (métricas: visitas/funil, leads e conversão, origem)

## Contexto

Sem medir visitas e cliques, não há taxa de conversão nem como saber onde a landing perde gente. O CRM precisa disso sem depender de ferramenta externa.

## Problema

Só o lead final seria visível; o topo e o meio do funil ficam cegos.

## Objetivo

Registrar eventos anônimos de primeira parte na landing para montar o funil visita → CTA → formulário → lead, por canal e campanha.

## Atores

Visitante (gera eventos). Admin (vê no REQ-008).

## Regras de negócio

1. Eventos: `page_view`, `cta_click` (com o alvo: hero, nav, cta-bar, plano X), `form_view` (formulário entrou na tela), `form_start` (primeiro campo focado), `form_submit` (envio aceito).
2. Visitante anônimo identificado por `visitor_id` (uuid no `localStorage`); sessão por `session_id` (uuid no `sessionStorage`).
3. Cada evento carrega o canal e a UTM first-touch do visitante.
4. `form_view` e `form_start` contam no máximo uma vez por sessão.
5. Respeitar o Do Not Track / Global Privacy Control: não enviar eventos.
6. Bots conhecidos (user-agent) são ignorados.

## Escopo

### Incluído
- Coleta no front, endpoint de ingestão, armazenamento, ligação `visitor_id` ↔ lead.

### Fora do escopo
- Pixel do Meta / Conversions API (pode virar REQ).
- Mapas de calor e gravação de sessão.

## Critérios de aceite

1. Abrir a landing grava um `page_view`.
2. Clicar em "Agendar conversa" no hero grava `cta_click` com `target=hero`.
3. Rolar até o formulário grava um `form_view` por sessão.
4. O lead criado no REQ-002 tem o mesmo `visitor_id` dos eventos.
5. Com GPC/DNT ativo nenhum evento é enviado.
6. O tracking não atrasa a página (envio com `sendBeacon`, resposta 204).

## Restrições

Sem cookies de terceiros, sem dependência nova, IP só como hash.

## Dependências

REQ-002 (origem first-touch e `visitor_id` no lead).

## Decisões pendentes

| # | Pergunta | Recomendação |
|---|---|---|
| `DP-0301` | Retenção dos eventos brutos? | 13 meses, com comando agendado que apaga os antigos |
| `DP-0302` | Banner de cookies? | Não necessário: sem cookie, só armazenamento local anônimo; citar na política de privacidade |

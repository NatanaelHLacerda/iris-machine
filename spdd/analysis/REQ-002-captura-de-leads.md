# Análise — REQ-002 Captura de leads
**Status:** RASCUNHO

## Conceitos

- **Entidades:** `Lead`, `LeadSubmission` (cada envio), origem (UTM/canal).
- **Operações:** capturar UTM, enviar formulário, validar, deduplicar por e-mail, classificar canal.

## O que existe

| Item | Estado |
|---|---|
| `resources/views/partials/lead.blade.php` | Form `data-lead-form`, `action="#"`, campos `name,email,whatsapp,level,plan,goal,site`; `data-error="*"` por campo e `data-error="form"`; bloco `data-lead-success` |
| `resources/js/app.js` `initLeadForm` | Só `preventDefault` |
| `resources/js/app.js` `initPlanLinks` | Botão de plano pré-seleciona `#lead-plan` |
| `config/iris.php` | `levels` (estudante, estagiario, junior, outro); `plans` (sessao, mentoria, squad) |
| `bootstrap/app.php` | JSON de exceção para `expectsJson()` (envio por `fetch` recebe 422 em JSON) |

## Riscos

- **Spam/bot:** honeypot já existe; somar rate limit por IP.
- **UTM perdida:** a UTM está na URL de entrada; se o visitante navegar por âncora, a query continua, mas recarregar sem ela perde a origem. Guardar em `sessionStorage` no primeiro acesso e no `localStorage` como first-touch de 30 dias.
- **LGPD:** dados pessoais; guardar só o necessário e o IP como hash.
- **Contrato com o REQ-003:** o `visitor_id` do tracking deve ligar o lead aos eventos; prever a coluna agora.

## Direções de design

1. **Envio via `fetch` com JSON + fallback de POST normal** (recomendada): mantém a experiência atual (sucesso sem recarregar) e funciona sem JS.
2. Só POST tradicional com redirect: mais simples, porém recarrega e perde o scroll/animação.

## Impacto

Model e migrations novas; `lead.blade.php` ganha `action` real e campos ocultos de origem; `app.js` ganha captura de UTM e envio.

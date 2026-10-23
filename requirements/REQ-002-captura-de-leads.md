# REQ-002 — Captura de leads da landing com origem
**Status:** APROVADO — Natanael, 2026-10-23, segue a cadeia (*"aprovado, siga todas as cadeias de ponta a ponta automizadas."*): decisões pendentes na recomendação.
**Origem:** conversa de 2026-10-23 (CRM de growth; investimento no Meta)

## Contexto

O formulário `#agendar` da landing coleta nome, e-mail, WhatsApp, momento (`level`), formato de interesse (`plan`) e objetivo (`goal`), mas o envio é bloqueado no JS (`resources/js/app.js`, `initLeadForm`). Nenhum lead é salvo.

## Problema

Todo lead que preenche o formulário se perde, e não se sabe de qual campanha ele veio.

## Objetivo

Salvar cada envio como lead com a origem (UTM, referrer, página de entrada), base de todo o CRM.

## Atores

Visitante da landing (envia). Admin (consome nos REQs seguintes).

## Regras de negócio

1. Obrigatórios: nome, e-mail, momento. Opcionais: WhatsApp, formato e objetivo.
2. `level` e `plan` só aceitam os valores de `config('iris.levels')` e `config('iris.plans')`.
3. Honeypot `site` preenchido → resposta de sucesso falsa, nada é salvo.
4. A origem é a do primeiro toque na sessão do visitante (first-touch): `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`, `fbclid`, referrer e landing path.
5. Canal derivado: `meta` (utm_source facebook/instagram/meta/fb/ig ou fbclid), `google`, `organico` (referrer de buscador sem UTM), `direto` (sem referrer e sem UTM), `outro`.
6. Mesmo e-mail enviando de novo: o lead existente é reaproveitado e o novo envio fica registrado no histórico (sem duplicar o lead).
7. Lead novo entra no pipeline com status `novo`.

## Escopo

### Incluído
- Endpoint de envio, validação, persistência, captura de UTM no front, feedback de sucesso/erro no formulário.

### Fora do escopo
- E-mail/WhatsApp automático ao lead.
- Notificação ao admin (pode virar REQ próprio).

## Critérios de aceite

1. Envio válido mostra a mensagem de sucesso e cria o lead com status `novo`.
2. Erros de validação aparecem embaixo de cada campo (`data-error`).
3. Acessar `/?utm_source=facebook&utm_campaign=x`, navegar e enviar grava UTM e canal `meta`.
4. Honeypot preenchido não grava nada.
5. Reenvio com o mesmo e-mail não duplica o lead.
6. Mais de 5 envios por minuto do mesmo IP são recusados.

## Restrições

Sem dependência nova; o IP não é guardado em texto puro (LGPD).

## Dependências

Nenhuma.

## Decisões pendentes

| # | Pergunta | Recomendação |
|---|---|---|
| `DP-0201` | Aviso de privacidade/consentimento no formulário? | Uma linha abaixo do botão citando o uso do contato; sem checkbox |

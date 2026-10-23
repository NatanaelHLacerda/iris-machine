# REQ-009 — Dados de demonstração
**Status:** APROVADO — Natanael, 2026-10-23, segue a cadeia (*"aprovado, siga todas as cadeias de ponta a ponta automizadas."*): decisões pendentes na recomendação.
**Origem:** conversa de 2026-10-23 (*"utilize dados mockados para preencher nosso crm para termos uma noção do futuro"*)

## Contexto

Antes de haver tráfego real, o CRM fica vazio e não dá para avaliar telas e métricas.

## Problema

Sem dados, não se enxerga como o CRM vai se comportar com a operação rodando.

## Objetivo

Popular o banco local com 6 meses de operação fictícia e coerente: visitas, leads, pipeline, vendas, assinaturas, gasto no Meta e caixa.

## Atores

Desenvolvedor/admin em ambiente local.

## Regras de negócio

1. Cenário plausível para o negócio: crescimento gradual até ~R$ 3.000–4.500/mês no último mês, Meta como canal principal, orgânico e direto menores.
2. Coerência entre camadas: eventos → leads → etapas → vendas → pagamentos; gasto diário por campanha com CPL entre R$ 8 e R$ 25.
3. 3 a 4 campanhas do Meta com UTMs no padrão da `DP-0602`, uma delas deliberadamente no vermelho (CAC > LTV).
4. Mix de formatos: maioria mentoria, algumas sessões avulsas (parte vira mentoria), 1–2 squads.
5. Churn mensal realista (10–20%), algumas cobranças atrasadas, follow-ups vencidos e leads esfriando hoje.
6. Nomes e e-mails fictícios (`@example.com`), nunca dados reais.
7. Nunca roda em produção.

## Escopo

### Incluído
- Seeder de demonstração e comando `crm:demo` (com `--fresh` para recriar).
- Usuário admin de demonstração no ambiente local.

### Fora do escopo
- Dados de demonstração em produção.

## Critérios de aceite

1. `php artisan crm:demo --fresh` popula o banco e imprime as credenciais locais.
2. Todas as telas do CRM mostram dados coerentes (funil decrescente, ROAS por campanha variado, caixa positivo).
3. O comando recusa rodar com `APP_ENV=production`.
4. Rodar duas vezes sem `--fresh` não duplica nada (avisa e para).

## Restrições

Usar as factories dos REQs 002–007; sem dependência nova.

## Dependências

REQ-002 a REQ-007 (models e factories).

## Decisões pendentes

| # | Pergunta | Recomendação |
|---|---|---|
| `DP-0901` | Credenciais do admin de demo | `admin@iris.test` / senha aleatória impressa no terminal |

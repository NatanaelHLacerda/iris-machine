# REQ-001 — Acesso ao `/gestao-crm` (auth e casca do CRM)
**Status:** RASCUNHO
**Origem:** conversa de 2026-10-23 (*"podermos acessa-lo em /gestao-crm"*, *"auth será necessário."*)

## Contexto

O projeto hoje é só a landing (`GET /`). O CRM vai morar no mesmo Laravel, atrás de login, com a estrutura de telas do `legalizai-projeto/crm-frontend` e a identidade visual da landing.

## Problema

Não há área administrativa nem autenticação; nada impede que dados de leads e faturamento fiquem expostos.

## Objetivo

Área `/gestao-crm` protegida por login, com a casca (sidebar + área principal) onde as próximas telas se encaixam.

## Atores

Admin (um usuário, o dono do negócio).

## Regras de negócio

1. Sem cadastro público: o admin é criado por comando Artisan.
2. Toda rota `/gestao-crm/*` exige sessão, exceto a tela de login.
3. Tentativas de login são limitadas (força bruta).
4. Sair encerra a sessão e volta para o login.

## Escopo

### Incluído
- Tela de login (e-mail + senha + "manter conectado").
- Logout.
- Comando `crm:create-admin`.
- Layout do CRM: sidebar com grupos (Growth, Marketing, Vendas, Financeiro), cartão do usuário com sair, área principal.
- `/gestao-crm` redireciona para o dashboard (placeholder até o REQ-008).
- `robots` / `noindex` nas páginas do CRM.

### Fora do escopo
- Recuperação de senha por e-mail (depende de SMTP).
- Múltiplos usuários, papéis e permissões.
- 2FA.

## Critérios de aceite

1. Acessar `/gestao-crm` sem sessão leva ao login.
2. Login com credenciais corretas entra; incorretas mostram erro genérico sem dizer qual campo errou.
3. Após 5 tentativas erradas em 1 minuto, o login bloqueia temporariamente.
4. `php artisan crm:create-admin` cria o admin pedindo nome, e-mail e senha.
5. O layout segue a identidade da landing e funciona no celular (sidebar vira menu).
6. Sair encerra a sessão.

## Restrições

Sem pacote novo (Breeze/Fortify/Filament exigem aprovação). Usar `Auth` nativo do Laravel.

## Dependências

Tabela `users` (já existe).

## Decisões pendentes

| # | Pergunta | Recomendação |
|---|---|---|
| `DP-0101` | Recuperação de senha agora? | Não; recriar a senha pelo comando até haver SMTP |

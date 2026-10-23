# REQ-004 — Leads e pipeline
**Status:** RASCUNHO
**Origem:** conversa de 2026-10-23 (*"comportamento de crm"*; jornada: formulário → conversa de diagnóstico → formato → fechamento)

## Contexto

A venda da Iris Machine é consultiva: o lead pede uma conversa, recebe um diagnóstico e uma indicação de formato (sessão, mentoria, squad). O CRM precisa acompanhar cada lead nesse caminho.

## Problema

Sem pipeline, leads esfriam sem follow-up e não se sabe em que etapa as vendas travam.

## Objetivo

Lista de leads, pipeline em kanban e ficha (dossiê) de cada lead com histórico, anotações e próxima ação.

## Atores

Admin.

## Regras de negócio

1. Etapas: **Novo → Contato feito → Conversa agendada → Diagnóstico feito → Proposta → Fechado | Perdido**.
2. Mover para **Perdido** exige motivo: sem resposta, preço, timing, não é o público, escolheu outro, outro (com texto).
3. Mover para **Fechado** acontece ao registrar a venda (REQ-005), nunca arrastando só o card.
4. Lead pode voltar de etapa (venda consultiva não é forward-only), mas toda mudança fica no histórico com data.
5. **Próxima ação**: texto + data; lead com próxima ação vencida aparece destacado como "atrasado".
6. Lead em **Novo** há mais de 24h sem contato é sinalizado ("esfriando").
7. Perdido pode ser reaberto (volta para Contato feito).

## Escopo

### Incluído
- `/gestao-crm/leads`: tabela com busca (nome, e-mail, WhatsApp), filtros (etapa, canal, campanha, momento, formato, período) e números de resumo.
- `/gestao-crm/pipeline`: kanban por etapa com drag-and-drop e contadores.
- `/gestao-crm/leads/{lead}`: dossiê com dados, origem/UTM, envios do formulário, linha do tempo (mudanças de etapa, notas, eventos do REQ-003), próxima ação, link para WhatsApp/e-mail.
- Cadastro manual de lead (indicação, Instagram direto) com canal escolhido.
- Exportar CSV da lista filtrada.

### Fora do escopo
- Envio de mensagens pelo CRM (WhatsApp API).
- Agenda/calendário integrado (o "agendado" é só a data).

## Critérios de aceite

1. Lead novo do formulário aparece em Novo no kanban e no topo da lista.
2. Arrastar um card muda a etapa e registra no histórico.
3. Mover para Perdido sem motivo é recusado.
4. Nota adicionada aparece na linha do tempo com data.
5. Próxima ação vencida destaca o card e a linha.
6. Filtros combinados refletem nos números de resumo.
7. Tudo funciona sem arrastar (select de etapa na ficha) e no celular.

## Restrições

Sem dependência nova (drag-and-drop com HTML5 nativo).

## Dependências

REQ-001 (acesso), REQ-002 (leads), REQ-003 (eventos na linha do tempo, opcional).

## Decisões pendentes

| # | Pergunta | Recomendação |
|---|---|---|
| `DP-0401` | Etapas configuráveis pela tela? | Não; fixas em enum até a operação pedir |
| `DP-0402` | Apagar lead (pedido LGPD)? | Sim, com confirmação, apagando envios e eventos ligados |

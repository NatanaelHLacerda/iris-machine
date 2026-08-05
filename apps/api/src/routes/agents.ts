import type { FastifyInstance } from "fastify";
import { agentConfigSchema, type Agent, type AgentConfig, type ChatMessage } from "@iris/shared";
import { authenticate, requireUser } from "../plugins/authenticate.js";
import { HttpError, sendError } from "../lib/errors.js";

/**
 * Armazenamento em memória — placeholder até as tabelas de agentes existirem
 * no Supabase. Só a autenticação está de fato persistida (Supabase Auth).
 * Reiniciar a API zera estes dados.
 */
const agents: Agent[] = [
  {
    id: "jimmy",
    name: "Jimmy",
    role: "Conversão e portabilidade de código entre plataformas",
    instructions:
      "Analisa projetos em diferentes linguagens e frameworks, e reconstrói cada tela ou componente na plataforma de destino — pronto para integrar, já validado e revisado.",
    model: "Claude Opus",
    avatarUrl: "/uploads/761a440f4d38e77c845b67badf122797.jpg",
    status: "online",
    active: true,
    vpsAddress: "vps.hostinger.com:8443",
    lastRunAt: new Date().toISOString(),
    tasksDone: 42,
  },
];

const configsByAgent = new Map<string, AgentConfig>();
const messagesByAgent = new Map<string, ChatMessage[]>();

function findAgent(id: string): Agent {
  const agent = agents.find((a) => a.id === id);
  if (!agent) throw new HttpError(404, "agent_not_found", "Agente não encontrado");
  return agent;
}

export async function agentRoutes(app: FastifyInstance): Promise<void> {
  // Tudo aqui exige sessão válida.
  app.addHook("preHandler", authenticate);

  /** GET /agents — lista os agentes do usuário. */
  app.get("/", async (request, reply) => {
    try {
      requireUser(request);
      return reply.send({ agents });
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** GET /agents/:id */
  app.get<{ Params: { id: string } }>("/:id", async (request, reply) => {
    try {
      requireUser(request);
      return reply.send({ agent: findAgent(request.params.id) });
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** GET /agents/:id/config — o token do GitHub nunca é devolvido. */
  app.get<{ Params: { id: string } }>("/:id/config", async (request, reply) => {
    try {
      requireUser(request);
      const agent = findAgent(request.params.id);
      const config = configsByAgent.get(agent.id) ?? null;
      if (!config) return reply.send({ config: null, hasGithubToken: false });

      const { githubToken, ...safe } = config;
      return reply.send({ config: safe, hasGithubToken: Boolean(githubToken) });
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** PUT /agents/:id/config — salva a configuração do agente. */
  app.put<{ Params: { id: string } }>("/:id/config", async (request, reply) => {
    try {
      requireUser(request);
      const agent = findAgent(request.params.id);
      const parsed = agentConfigSchema.parse(request.body);

      const previous = configsByAgent.get(agent.id);
      // Token vazio no payload significa "manter o que já está salvo".
      const githubToken = parsed.githubToken || previous?.githubToken;
      const next: AgentConfig = { ...parsed, githubToken };

      configsByAgent.set(agent.id, next);
      agent.active = next.active;
      agent.vpsAddress = next.vps;

      const { githubToken: _omitted, ...safe } = next;
      return reply.send({ config: safe, hasGithubToken: Boolean(githubToken) });
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** GET /agents/:id/messages */
  app.get<{ Params: { id: string } }>("/:id/messages", async (request, reply) => {
    try {
      requireUser(request);
      const agent = findAgent(request.params.id);
      return reply.send({ messages: messagesByAgent.get(agent.id) ?? [] });
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /**
   * POST /agents/:id/messages — envia mensagem ao agente.
   * A resposta ainda é simulada; aqui entra a chamada real ao agente na VPS.
   */
  app.post<{ Params: { id: string }; Body: { content?: string } }>(
    "/:id/messages",
    async (request, reply) => {
      try {
        requireUser(request);
        const agent = findAgent(request.params.id);
        const content = (request.body?.content ?? "").trim();
        if (!content) throw new HttpError(422, "empty_message", "Mensagem vazia");

        const history = messagesByAgent.get(agent.id) ?? [];
        const userMessage: ChatMessage = {
          id: crypto.randomUUID(),
          agentId: agent.id,
          author: "user",
          content,
          createdAt: new Date().toISOString(),
        };
        const agentMessage: ChatMessage = {
          id: crypto.randomUUID(),
          agentId: agent.id,
          author: "agent",
          content:
            "Entendido. Vou analisar a estrutura e retornar com o plano de portabilidade em instantes.",
          createdAt: new Date().toISOString(),
        };

        messagesByAgent.set(agent.id, [...history, userMessage, agentMessage]);
        return reply.status(201).send({ messages: [userMessage, agentMessage] });
      } catch (err) {
        return sendError(reply, err);
      }
    },
  );
}

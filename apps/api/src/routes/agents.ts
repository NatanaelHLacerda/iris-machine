import type { FastifyInstance } from "fastify";
import {
  agentConfigSchema,
  type Agent,
  type AgentConfig,
  type AgentStatus,
  type ChatMessage,
  type DashboardStats,
} from "@iris/shared";
import { authenticate, requireUser } from "../plugins/authenticate.js";
import { HttpError, sendError } from "../lib/errors.js";
import { sendMessageToHermesAgent, getHermesStatus } from "../lib/hermesClient.js";
import { supabaseAdmin } from "../lib/supabase.js";

/**
 * Persistência real em Supabase (ver supabase/migrations/0001_agents_persistence.sql).
 * `conversations`, `lastRunAt` e `status` do tipo `Agent` são sempre calculados
 * a partir de `messages` e do Hermes ao servir a resposta — nunca armazenados
 * como valor fixo, pra não voltar a divergir do que de fato aconteceu.
 */

interface AgentRow {
  id: string;
  name: string;
  role: string;
  instructions: string;
  model: string;
  avatar_url: string | null;
  active: boolean;
  vps_address: string | null;
}

interface MessageRow {
  id: string;
  agent_id: string;
  author: "user" | "agent";
  content: string;
  created_at: string;
}

function db() {
  if (!supabaseAdmin) {
    throw new HttpError(
      500,
      "supabase_not_configured",
      "SUPABASE_SERVICE_ROLE_KEY não configurada — necessária para ler/gravar agentes",
    );
  }
  return supabaseAdmin;
}

async function findAgentRow(id: string): Promise<AgentRow> {
  const { data, error } = await db().from("agents").select("*").eq("id", id).maybeSingle();
  if (error) throw new HttpError(502, "supabase_error", error.message);
  if (!data) throw new HttpError(404, "agent_not_found", "Agente não encontrado");
  return data as AgentRow;
}

async function listAgentRows(): Promise<AgentRow[]> {
  const { data, error } = await db().from("agents").select("*").order("name");
  if (error) throw new HttpError(502, "supabase_error", error.message);
  return (data ?? []) as AgentRow[];
}

async function messagesForAgent(agentId: string): Promise<MessageRow[]> {
  const { data, error } = await db()
    .from("messages")
    .select("*")
    .eq("agent_id", agentId)
    .order("created_at", { ascending: true });
  if (error) throw new HttpError(502, "supabase_error", error.message);
  return (data ?? []) as MessageRow[];
}

function toChatMessage(row: MessageRow): ChatMessage {
  return { id: row.id, agentId: row.agent_id, author: row.author, content: row.content, createdAt: row.created_at };
}

/** Média (segundos) entre cada mensagem de usuário e a resposta do agente logo em seguida. */
function averageResponseSeconds(rows: MessageRow[]): number | null {
  const deltas: number[] = [];
  for (let i = 1; i < rows.length; i++) {
    const current = rows[i];
    const previous = rows[i - 1];
    if (current && previous && current.author === "agent" && previous.author === "user") {
      const deltaMs = new Date(current.created_at).getTime() - new Date(previous.created_at).getTime();
      if (deltaMs >= 0) deltas.push(deltaMs / 1000);
    }
  }
  if (!deltas.length) return null;
  return deltas.reduce((a, b) => a + b, 0) / deltas.length;
}

async function toAgent(row: AgentRow): Promise<Agent> {
  const messages = await messagesForAgent(row.id);
  const conversations = messages.filter((m) => m.author === "user").length;
  const lastMessage = messages.at(-1) ?? null;
  const status: AgentStatus = row.active ? await getHermesStatus(row.id) : "offline";

  return {
    id: row.id,
    name: row.name,
    role: row.role,
    instructions: row.instructions,
    model: row.model,
    avatarUrl: row.avatar_url,
    status,
    active: row.active,
    vpsAddress: row.vps_address,
    lastRunAt: lastMessage?.created_at ?? null,
    conversations,
  };
}

function startOfTodayIso(): string {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  return d.toISOString();
}

export async function agentRoutes(app: FastifyInstance): Promise<void> {
  // Tudo aqui exige sessão válida.
  app.addHook("preHandler", authenticate);

  /** GET /agents — lista os agentes com métricas reais. */
  app.get("/", async (request, reply) => {
    try {
      requireUser(request);
      const rows = await listAgentRows();
      const agents = await Promise.all(rows.map(toAgent));
      return reply.send({ agents });
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** GET /agents/stats — métricas agregadas reais pro painel. */
  app.get("/stats", async (request, reply) => {
    try {
      requireUser(request);
      const rows = await listAgentRows();
      const activeAgents = rows.filter((r) => r.active).length;

      const { data: todayRows, error: todayErr } = await db()
        .from("messages")
        .select("id")
        .eq("author", "user")
        .gte("created_at", startOfTodayIso());
      if (todayErr) throw new HttpError(502, "supabase_error", todayErr.message);

      const { data: allRows, error: allErr } = await db()
        .from("messages")
        .select("agent_id, author, created_at")
        .order("created_at", { ascending: true });
      if (allErr) throw new HttpError(502, "supabase_error", allErr.message);

      const byAgent = new Map<string, MessageRow[]>();
      for (const m of (allRows ?? []) as MessageRow[]) {
        const list = byAgent.get(m.agent_id) ?? [];
        list.push(m);
        byAgent.set(m.agent_id, list);
      }
      const averages = [...byAgent.values()]
        .map(averageResponseSeconds)
        .filter((v): v is number => v !== null);
      const avgResponseSeconds = averages.length
        ? averages.reduce((a, b) => a + b, 0) / averages.length
        : null;

      const stats: DashboardStats = {
        activeAgents,
        conversationsToday: (todayRows ?? []).length,
        avgResponseSeconds,
      };
      return reply.send({ stats });
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** GET /agents/:id */
  app.get<{ Params: { id: string } }>("/:id", async (request, reply) => {
    try {
      requireUser(request);
      const row = await findAgentRow(request.params.id);
      return reply.send({ agent: await toAgent(row) });
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** GET /agents/:id/config — o token do GitHub nunca é devolvido. */
  app.get<{ Params: { id: string } }>("/:id/config", async (request, reply) => {
    try {
      requireUser(request);
      const agent = await findAgentRow(request.params.id);

      const { data, error } = await db()
        .from("agent_configs")
        .select("config")
        .eq("agent_id", agent.id)
        .maybeSingle();
      if (error) throw new HttpError(502, "supabase_error", error.message);

      const config = (data?.config as AgentConfig | undefined) ?? null;
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
      const agent = await findAgentRow(request.params.id);
      const parsed = agentConfigSchema.parse(request.body);

      const { data: existing, error: readErr } = await db()
        .from("agent_configs")
        .select("config")
        .eq("agent_id", agent.id)
        .maybeSingle();
      if (readErr) throw new HttpError(502, "supabase_error", readErr.message);

      const previous = existing?.config as AgentConfig | undefined;
      // Token vazio no payload significa "manter o que já está salvo".
      const githubToken = parsed.githubToken || previous?.githubToken;
      const next: AgentConfig = { ...parsed, githubToken };

      const { error: upsertErr } = await db()
        .from("agent_configs")
        .upsert({ agent_id: agent.id, config: next, updated_at: new Date().toISOString() });
      if (upsertErr) throw new HttpError(502, "supabase_error", upsertErr.message);

      const { error: agentUpdateErr } = await db()
        .from("agents")
        .update({ active: next.active, vps_address: next.vps })
        .eq("id", agent.id);
      if (agentUpdateErr) throw new HttpError(502, "supabase_error", agentUpdateErr.message);

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
      const agent = await findAgentRow(request.params.id);
      const rows = await messagesForAgent(agent.id);
      return reply.send({ messages: rows.map(toChatMessage) });
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** POST /agents/:id/messages — envia mensagem ao agente via hermes serve na VPS. */
  app.post<{ Params: { id: string }; Body: { content?: string } }>(
    "/:id/messages",
    async (request, reply) => {
      try {
        requireUser(request);
        const agent = await findAgentRow(request.params.id);
        const content = (request.body?.content ?? "").trim();
        if (!content) throw new HttpError(422, "empty_message", "Mensagem vazia");

        // Grava a mensagem do usuário antes de chamar o Hermes: se o agente
        // falhar, a conversa real ainda fica registrada (métricas não perdem o evento).
        const { data: insertedUser, error: userErr } = await db()
          .from("messages")
          .insert({ agent_id: agent.id, author: "user", content })
          .select("*")
          .single();
        if (userErr) throw new HttpError(502, "supabase_error", userErr.message);
        const userMessage = toChatMessage(insertedUser as MessageRow);

        const replyText = await sendMessageToHermesAgent(agent.id, content);

        const { data: insertedAgent, error: agentErr } = await db()
          .from("messages")
          .insert({ agent_id: agent.id, author: "agent", content: replyText })
          .select("*")
          .single();
        if (agentErr) throw new HttpError(502, "supabase_error", agentErr.message);
        const agentMessage = toChatMessage(insertedAgent as MessageRow);

        return reply.status(201).send({ messages: [userMessage, agentMessage] });
      } catch (err) {
        return sendError(reply, err);
      }
    },
  );
}

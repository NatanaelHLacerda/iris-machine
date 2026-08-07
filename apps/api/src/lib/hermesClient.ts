/**
 * Cliente para o backend `hermes serve` (JSON-RPC sobre WebSocket) rodando
 * na VPS de cada agente. Protocolo replicado de web/src/lib/gatewayClient.ts
 * e apps/shared/src/json-rpc-gateway.ts no repositório hermes-agent:
 *
 *   1. POST /auth/password-login  → cookie de sessão (Set-Cookie)
 *   2. POST /api/auth/ws-ticket   → ticket de uso único (Bearer do passo 1)
 *   3. WS   /api/ws?ticket=...    → session.create, depois prompt.submit
 *   4. Evento `message.complete` no WS carrega a resposta final do agente
 *
 * Credenciais e URL de cada agente vêm de HERMES_AGENTS (JSON), nunca do
 * objeto Agent devolvido ao browser — mesmo princípio do githubToken em
 * agents.ts.
 */

import { WebSocket } from "ws";
import { z } from "zod";
import { HttpError } from "./errors.js";
import { env } from "../env.js";

const hermesAgentConfigSchema = z.object({
  baseUrl: z.string().url(),
  username: z.string().min(1),
  password: z.string().min(1),
});

const hermesAgentsSchema = z.record(hermesAgentConfigSchema);

type HermesAgentConfig = z.infer<typeof hermesAgentConfigSchema>;

let cachedAgents: Record<string, HermesAgentConfig> | null = null;

function loadHermesAgents(): Record<string, HermesAgentConfig> {
  if (cachedAgents) return cachedAgents;
  if (!env.HERMES_AGENTS) {
    cachedAgents = {};
    return cachedAgents;
  }
  let json: unknown;
  try {
    json = JSON.parse(env.HERMES_AGENTS);
  } catch {
    throw new Error("HERMES_AGENTS não é um JSON válido");
  }
  cachedAgents = hermesAgentsSchema.parse(json);
  return cachedAgents;
}

function getHermesAgentConfig(agentId: string): HermesAgentConfig | null {
  return loadHermesAgents()[agentId] ?? null;
}

// ---------------------------------------------------------------------------
// Login + cache do access token (basic_auth: TTL longo, ver dashboard.
// basic_auth.session_ttl_seconds no config.yaml do hermes-agent — default
// 12h — então cachear em memória evita relogar a cada mensagem e esbarrar
// no rate limit de /auth/password-login, 10 tentativas/min).
// ---------------------------------------------------------------------------

interface CachedToken {
  accessToken: string;
  expiresAt: number;
}

const tokenCache = new Map<string, CachedToken>();

/** Extrai valor + Max-Age do cookie de sessão dentre os Set-Cookie da resposta.
 *  O nome real varia (__Host-hermes_session_at em HTTPS sem prefixo de path,
 *  __Secure- com prefixo, ou bare em loopback) — casamos pelo sufixo. */
function parseSessionAtCookie(
  setCookies: readonly string[],
): { value: string; maxAgeSeconds: number } | null {
  for (const raw of setCookies) {
    const [pair, ...attrs] = raw.split(";").map((s) => s.trim());
    if (!pair) continue;
    const eq = pair.indexOf("=");
    if (eq === -1) continue;
    const name = pair.slice(0, eq);
    if (!name.endsWith("hermes_session_at")) continue;

    const value = pair.slice(eq + 1);
    const maxAgeAttr = attrs.find((a) => a.toLowerCase().startsWith("max-age="));
    const maxAgeSeconds = maxAgeAttr ? Number(maxAgeAttr.slice(maxAgeAttr.indexOf("=") + 1)) : 3600;
    return { value, maxAgeSeconds: Number.isFinite(maxAgeSeconds) ? maxAgeSeconds : 3600 };
  }
  return null;
}

async function loginHermes(agentId: string, config: HermesAgentConfig): Promise<CachedToken> {
  const res = await fetch(`${config.baseUrl}/auth/password-login`, {
    method: "POST",
    headers: { "content-type": "application/json" },
    body: JSON.stringify({
      provider: "basic",
      username: config.username,
      password: config.password,
      next: "",
    }),
  });

  if (!res.ok) {
    throw new HttpError(
      502,
      "hermes_auth_failed",
      `Falha ao autenticar no Hermes do agente "${agentId}": HTTP ${res.status}`,
    );
  }

  const setCookies = res.headers.getSetCookie?.() ?? [];
  const parsed = parseSessionAtCookie(setCookies);
  if (!parsed) {
    throw new HttpError(
      502,
      "hermes_auth_failed",
      `Login no Hermes do agente "${agentId}" não retornou cookie de sessão`,
    );
  }

  // Margem de 30s para não usar um token prestes a expirar.
  const expiresAt = Date.now() + Math.max(0, parsed.maxAgeSeconds - 30) * 1000;
  return { accessToken: parsed.value, expiresAt };
}

async function getAccessToken(agentId: string, config: HermesAgentConfig): Promise<string> {
  const cached = tokenCache.get(agentId);
  if (cached && cached.expiresAt > Date.now()) {
    return cached.accessToken;
  }
  const fresh = await loginHermes(agentId, config);
  tokenCache.set(agentId, fresh);
  return fresh.accessToken;
}

async function mintWsTicket(agentId: string, baseUrl: string, accessToken: string): Promise<string> {
  const res = await fetch(`${baseUrl}/api/auth/ws-ticket`, {
    method: "POST",
    headers: { authorization: `Bearer ${accessToken}` },
  });
  if (!res.ok) {
    throw new HttpError(
      502,
      "hermes_ticket_failed",
      `Falha ao obter ticket de WebSocket do Hermes (agente "${agentId}"): HTTP ${res.status}`,
    );
  }
  const body = (await res.json()) as { ticket: string };
  return body.ticket;
}

// ---------------------------------------------------------------------------
// Turno de chat via WebSocket JSON-RPC
// ---------------------------------------------------------------------------

const CHAT_TIMEOUT_MS = 120_000;

interface JsonRpcFrame {
  id?: number | string | null;
  method?: string;
  params?: { type?: string; session_id?: string; payload?: { text?: string; status?: string } };
  result?: { session_id?: string };
  error?: { message?: string };
}

function runChatTurn(agentId: string, baseUrl: string, ticket: string, text: string): Promise<string> {
  return new Promise((resolve, reject) => {
    const wsUrl = `${baseUrl.replace(/^http/, "ws")}/api/ws?ticket=${encodeURIComponent(ticket)}`;
    const socket = new WebSocket(wsUrl);

    let sessionId: string | null = null;
    let settled = false;
    let nextId = 1;
    const sessionCreateId = nextId;

    const timer = setTimeout(() => {
      finish(new HttpError(504, "hermes_timeout", `O agente "${agentId}" demorou demais para responder`));
    }, CHAT_TIMEOUT_MS);

    function finish(err: Error | null, value?: string) {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      try {
        socket.close();
      } catch {
        // conexão já pode estar fechada
      }
      if (err) reject(err);
      else resolve(value ?? "");
    }

    socket.on("open", () => {
      socket.send(
        JSON.stringify({ jsonrpc: "2.0", id: sessionCreateId, method: "session.create", params: {} }),
      );
    });

    socket.on("message", (raw) => {
      let frame: JsonRpcFrame;
      try {
        frame = JSON.parse(raw.toString());
      } catch {
        return;
      }

      if (frame.id === sessionCreateId) {
        if (frame.error) {
          finish(
            new HttpError(
              502,
              "hermes_session_failed",
              frame.error.message || `Falha ao criar sessão no Hermes (agente "${agentId}")`,
            ),
          );
          return;
        }
        sessionId = frame.result?.session_id ?? null;
        if (!sessionId) {
          finish(new HttpError(502, "hermes_session_failed", "Hermes não retornou session_id"));
          return;
        }
        socket.send(
          JSON.stringify({
            jsonrpc: "2.0",
            id: ++nextId,
            method: "prompt.submit",
            params: { session_id: sessionId, text },
          }),
        );
        return;
      }

      if (frame.method === "event" && sessionId && frame.params?.session_id === sessionId) {
        const type = frame.params.type;
        if (type === "message.complete") {
          const payload = frame.params.payload ?? {};
          if (payload.status === "error") {
            finish(
              new HttpError(502, "hermes_agent_error", payload.text || "O agente retornou um erro"),
            );
          } else {
            finish(null, String(payload.text ?? ""));
          }
        } else if (type === "error") {
          finish(
            new HttpError(502, "hermes_agent_error", `Erro no agente Hermes "${agentId}"`),
          );
        }
      }
    });

    socket.on("error", (err) => {
      finish(new HttpError(502, "hermes_ws_error", `Erro de conexão com o Hermes: ${err.message}`));
    });

    socket.on("close", (code) => {
      finish(
        new HttpError(502, "hermes_ws_closed", `Conexão com o Hermes encerrada inesperadamente (code ${code})`),
      );
    });
  });
}

export type HermesReachability = "online" | "offline" | "error";

/**
 * Reachability real do agente — reusa o cache de token de `getAccessToken`,
 * então na maioria das chamadas (token ainda válido) não bate na rede.
 * "offline" = sem HERMES_AGENTS pro id; "error" = configurado mas login falhou.
 */
export async function getHermesStatus(agentId: string): Promise<HermesReachability> {
  const config = getHermesAgentConfig(agentId);
  if (!config) return "offline";
  try {
    await getAccessToken(agentId, config);
    return "online";
  } catch {
    return "error";
  }
}

export async function sendMessageToHermesAgent(agentId: string, text: string): Promise<string> {
  const config = getHermesAgentConfig(agentId);
  if (!config) {
    throw new HttpError(
      424,
      "hermes_not_configured",
      `Agente "${agentId}" não tem um Hermes configurado (defina HERMES_AGENTS)`,
    );
  }
  const accessToken = await getAccessToken(agentId, config);
  const ticket = await mintWsTicket(agentId, config.baseUrl, accessToken);
  return runChatTurn(agentId, config.baseUrl, ticket, text);
}

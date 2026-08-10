import type {
  Agent,
  AgentConfig,
  AgentConfigDraft,
  ChatMessage,
  CreateAgentInput,
  DashboardStats,
} from "@iris/shared";
import { apiFetch } from "@/lib/api";

/** Config devolvida pela API — o token do GitHub nunca volta, só a indicação de que existe. */
export type SafeAgentConfig = Omit<AgentConfig, "githubToken">;

export interface AgentConfigResponse {
  config: SafeAgentConfig | null;
  hasGithubToken: boolean;
}

export const agentsApi = {
  list: () => apiFetch<{ agents: Agent[] }>("/agents"),

  create: (input: CreateAgentInput) =>
    apiFetch<{ agent: Agent }>("/agents", { method: "POST", body: input }),

  stats: () => apiFetch<{ stats: DashboardStats }>("/agents/stats"),

  get: (id: string) => apiFetch<{ agent: Agent }>(`/agents/${id}`),

  getConfig: (id: string) => apiFetch<AgentConfigResponse>(`/agents/${id}/config`),

  saveConfig: (id: string, config: AgentConfigDraft) =>
    apiFetch<AgentConfigResponse>(`/agents/${id}/config`, { method: "PUT", body: config }),

  messages: (id: string) => apiFetch<{ messages: ChatMessage[] }>(`/agents/${id}/messages`),

  sendMessage: (id: string, content: string) =>
    apiFetch<{ messages: ChatMessage[] }>(`/agents/${id}/messages`, {
      method: "POST",
      body: { content },
    }),
};

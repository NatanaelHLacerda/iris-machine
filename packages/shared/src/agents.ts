import { z } from "zod";

export const agentStatusSchema = z.enum(["online", "offline", "busy", "error"]);
export type AgentStatus = z.infer<typeof agentStatusSchema>;

export interface Agent {
  id: string;
  name: string;
  role: string;
  instructions: string;
  model: string;
  avatarUrl: string | null;
  status: AgentStatus;
  active: boolean;
  vpsAddress: string | null;
  lastRunAt: string | null;
  conversations: number;
}

export const designSystemComponentSchema = z.object({
  name: z.string(),
  loc: z.string(),
  usage: z.string(),
});

export const agentConfigSchema = z.object({
  vps: z.string().trim().min(1, "Endereço da VPS é obrigatório"),
  active: z.boolean().default(false),
  githubToken: z.string().trim().optional(),
  repoPrototype: z.string().trim().url("URL inválida"),
  repoTarget: z.string().trim().url("URL inválida"),
  projectName: z.string().trim().min(1, "Nome do projeto é obrigatório"),
  stack: z.string().trim().min(1, "Stack é obrigatória"),
  stackOther: z.string().trim().optional(),
  statePattern: z.string().trim().optional(),
  stateOrg: z.string().trim().optional(),
  routingMech: z.string().trim().optional(),
  routesRegistry: z.string().trim().optional(),
  routesAnim: z.string().trim().optional(),
  folderPattern: z.string().trim().optional(),
  dsPath: z.string().trim().optional(),
  modelsPath: z.string().trim().optional(),
  servicesPath: z.string().trim().optional(),
  fileNaming: z.string().trim().optional(),
  componentNaming: z.string().trim().optional(),
  styleTool: z.string().trim().optional(),
  typography: z.string().trim().optional(),
  dsComponents: z.array(designSystemComponentSchema).default([]),
  httpClient: z.string().trim().optional(),
  errorPattern: z.string().trim().optional(),
  deps: z.array(z.string()).default([]),
  notes: z.string().trim().optional(),
});

export type AgentConfig = z.infer<typeof agentConfigSchema>;

export interface ChatMessage {
  id: string;
  agentId: string;
  author: "user" | "agent";
  content: string;
  createdAt: string;
}

/** Métricas agregadas reais, calculadas a partir das mensagens e agentes persistidos. */
export interface DashboardStats {
  activeAgents: number;
  conversationsToday: number;
  /** Média (segundos) entre mensagem do usuário e resposta do agente. `null` sem dados suficientes. */
  avgResponseSeconds: number | null;
}

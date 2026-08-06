import "dotenv/config";
import { z } from "zod";

const envSchema = z.object({
  NODE_ENV: z.enum(["development", "test", "production"]).default("development"),
  PORT: z.coerce.number().int().positive().default(3333),
  HOST: z.string().default("0.0.0.0"),
  WEB_ORIGIN: z.string().default("http://localhost:5173"),
  SUPABASE_URL: z.string().url("SUPABASE_URL deve ser uma URL válida"),
  SUPABASE_ANON_KEY: z.string().min(1, "SUPABASE_ANON_KEY é obrigatória"),
  SUPABASE_SERVICE_ROLE_KEY: z.string().min(1).optional(),
  PASSWORD_RESET_REDIRECT_URL: z.string().url().optional(),
  REFRESH_COOKIE_NAME: z.string().default("iris_rt"),
  // JSON: { "<agentId>": { "baseUrl": "https://...", "username": "...", "password": "..." } }
  // Mapeia cada agente ao seu backend `hermes serve`. Nunca exposto ao browser.
  HERMES_AGENTS: z.string().optional(),
});

const parsed = envSchema.safeParse(process.env);

if (!parsed.success) {
  const issues = parsed.error.issues
    .map((i) => `  - ${i.path.join(".")}: ${i.message}`)
    .join("\n");
  console.error(`Variáveis de ambiente inválidas:\n${issues}\n\nCopie apps/api/.env.example para apps/api/.env e preencha.`);
  process.exit(1);
}

export const env = parsed.data;
export const isProd = env.NODE_ENV === "production";

/** Origens aceitas no CORS — aceita lista separada por vírgula. */
export const allowedOrigins = env.WEB_ORIGIN.split(",").map((o) => o.trim()).filter(Boolean);

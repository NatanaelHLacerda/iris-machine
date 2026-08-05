import {
  createClient,
  type SupabaseClient,
  type SupabaseClientOptions,
  type User,
} from "@supabase/supabase-js";
import WebSocket from "ws";
import type { AuthUser } from "@iris/shared";
import { env } from "../env.js";

type RealtimeTransport = NonNullable<SupabaseClientOptions<"public">["realtime"]>["transport"];

const clientOptions: SupabaseClientOptions<"public"> = {
  auth: {
    // A API é stateless: nada de persistir sessão no processo do servidor.
    persistSession: false,
    autoRefreshToken: false,
    detectSessionInUrl: false,
  },
  realtime: {
    // supabase-js exige um WebSocket na inicialização e o Node 20 não tem um nativo.
    // Não usamos Realtime aqui — isto apenas evita o erro de boot.
    transport: WebSocket as unknown as RealtimeTransport,
  },
};

/** Cliente público (anon key). Usado para signup/signin/refresh — respeita RLS. */
export const supabaseAnon: SupabaseClient = createClient(
  env.SUPABASE_URL,
  env.SUPABASE_ANON_KEY,
  clientOptions,
);

/**
 * Cliente administrativo (service role). Ignora RLS — use apenas em operações
 * server-side deliberadas. Ausente se SUPABASE_SERVICE_ROLE_KEY não estiver configurada.
 */
export const supabaseAdmin: SupabaseClient | null = env.SUPABASE_SERVICE_ROLE_KEY
  ? createClient(env.SUPABASE_URL, env.SUPABASE_SERVICE_ROLE_KEY, clientOptions)
  : null;

/** Cliente com o token do usuário, para consultas que devem respeitar RLS. */
export function supabaseForToken(accessToken: string): SupabaseClient {
  return createClient(env.SUPABASE_URL, env.SUPABASE_ANON_KEY, {
    ...clientOptions,
    global: { headers: { Authorization: `Bearer ${accessToken}` } },
  });
}

/** Converte o usuário do Supabase no formato exposto pela API. */
export function toAuthUser(user: User): AuthUser {
  const meta = user.user_metadata ?? {};
  return {
    id: user.id,
    email: user.email ?? null,
    name: (meta.name as string | undefined) ?? (meta.full_name as string | undefined) ?? null,
    avatarUrl: (meta.avatar_url as string | undefined) ?? null,
    role: (user.app_metadata?.role as string | undefined) ?? "user",
    createdAt: user.created_at,
  };
}

import type { FastifyReply, FastifyRequest } from "fastify";
import type { AuthUser } from "@iris/shared";
import { supabaseAnon, toAuthUser } from "../lib/supabase.js";
import { sendError, unauthorized } from "../lib/errors.js";

declare module "fastify" {
  interface FastifyRequest {
    user?: AuthUser;
    accessToken?: string;
  }
}

function extractBearer(request: FastifyRequest): string | null {
  const header = request.headers.authorization;
  if (!header) return null;
  const [scheme, token] = header.split(" ");
  if (!token || scheme?.toLowerCase() !== "bearer") return null;
  return token;
}

/**
 * preHandler que valida o access token contra o Supabase e popula request.user.
 * O token é verificado no servidor de auth — não confiamos em decodificar o JWT localmente.
 */
export async function authenticate(request: FastifyRequest, reply: FastifyReply): Promise<void> {
  try {
    const token = extractBearer(request);
    if (!token) throw unauthorized("Token de acesso ausente");

    const { data, error } = await supabaseAnon.auth.getUser(token);
    if (error || !data.user) throw unauthorized("Token de acesso inválido ou expirado");

    request.user = toAuthUser(data.user);
    request.accessToken = token;
  } catch (err) {
    sendError(reply, err);
  }
}

/** Garante request.user já preenchido por `authenticate`. */
export function requireUser(request: FastifyRequest): AuthUser {
  if (!request.user) throw unauthorized();
  return request.user;
}

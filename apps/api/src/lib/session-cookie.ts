import type { FastifyReply, FastifyRequest } from "fastify";
import type { Session } from "@supabase/supabase-js";
import type { AuthResponse } from "@iris/shared";
import { toAuthUser } from "./supabase.js";
import { env, isProd } from "../env.js";

const REFRESH_MAX_AGE_SECONDS = 60 * 60 * 24 * 30; // 30 dias

/**
 * O refresh token nunca chega ao JavaScript do navegador — vive só num cookie
 * httpOnly. O access token (curta duração) volta no corpo da resposta e o
 * front-end o mantém em memória.
 */
export function setRefreshCookie(reply: FastifyReply, refreshToken: string): void {
  reply.setCookie(env.REFRESH_COOKIE_NAME, refreshToken, {
    httpOnly: true,
    secure: isProd,
    sameSite: isProd ? "none" : "lax",
    path: "/",
    maxAge: REFRESH_MAX_AGE_SECONDS,
    signed: false,
  });
}

export function clearRefreshCookie(reply: FastifyReply): void {
  reply.clearCookie(env.REFRESH_COOKIE_NAME, { path: "/" });
}

export function readRefreshCookie(request: FastifyRequest): string | null {
  return request.cookies[env.REFRESH_COOKIE_NAME] ?? null;
}

/** Monta a resposta de autenticação e grava o cookie de refresh, quando houver sessão. */
export function buildAuthResponse(
  reply: FastifyReply,
  session: Session | null,
  user = session?.user,
): AuthResponse {
  if (session?.refresh_token) {
    setRefreshCookie(reply, session.refresh_token);
  }
  if (!user) {
    throw new Error("buildAuthResponse chamado sem usuário");
  }
  return {
    user: toAuthUser(user),
    session: session
      ? {
          accessToken: session.access_token,
          expiresAt: session.expires_at ?? null,
        }
      : null,
  };
}

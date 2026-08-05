import type { FastifyReply } from "fastify";
import { ZodError } from "zod";

export class HttpError extends Error {
  constructor(
    readonly statusCode: number,
    readonly code: string,
    message: string,
    readonly details?: unknown,
  ) {
    super(message);
    this.name = "HttpError";
  }
}

export const unauthorized = (message = "Não autenticado") =>
  new HttpError(401, "unauthorized", message);

export const badRequest = (message: string, details?: unknown) =>
  new HttpError(400, "bad_request", message, details);

/**
 * Traduz erro do Supabase Auth para HttpError.
 * Mensagens de login são deliberadamente genéricas para não revelar
 * se um e-mail existe na base (enumeração de usuários).
 */
export function fromSupabaseAuthError(
  err: { message: string; status?: number; code?: string },
  context: "signIn" | "signUp" | "refresh" | "reset" | "update",
): HttpError {
  const status = err.status ?? 400;
  const raw = err.message.toLowerCase();

  if (context === "signIn") {
    return new HttpError(401, "invalid_credentials", "E-mail ou senha inválidos");
  }
  if (context === "refresh") {
    return new HttpError(401, "invalid_refresh_token", "Sessão expirada, faça login novamente");
  }
  if (raw.includes("already registered") || raw.includes("already been registered")) {
    return new HttpError(409, "email_taken", "Já existe uma conta com este e-mail");
  }
  if (status === 429 || raw.includes("rate limit")) {
    return new HttpError(429, "rate_limited", "Muitas tentativas. Tente novamente em instantes");
  }
  if (raw.includes("weak password")) {
    return new HttpError(400, "weak_password", "Senha muito fraca");
  }
  return new HttpError(status >= 400 && status < 600 ? status : 400, err.code ?? "auth_error", err.message);
}

export function sendError(reply: FastifyReply, err: unknown): FastifyReply {
  if (err instanceof HttpError) {
    return reply.status(err.statusCode).send({
      error: { code: err.code, message: err.message, details: err.details },
    });
  }
  if (err instanceof ZodError) {
    return reply.status(422).send({
      error: {
        code: "validation_error",
        message: "Dados inválidos",
        details: err.issues.map((i) => ({ path: i.path.join("."), message: i.message })),
      },
    });
  }
  reply.log.error({ err }, "erro não tratado");
  return reply.status(500).send({
    error: { code: "internal_error", message: "Erro interno do servidor" },
  });
}

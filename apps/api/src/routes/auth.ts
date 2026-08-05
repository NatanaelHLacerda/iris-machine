import type { FastifyInstance } from "fastify";
import {
  requestPasswordResetSchema,
  signInSchema,
  signUpSchema,
  updatePasswordSchema,
} from "@iris/shared";
import { supabaseAnon, supabaseForToken, toAuthUser } from "../lib/supabase.js";
import { fromSupabaseAuthError, sendError, unauthorized } from "../lib/errors.js";
import {
  buildAuthResponse,
  clearRefreshCookie,
  readRefreshCookie,
} from "../lib/session-cookie.js";
import { authenticate, requireUser } from "../plugins/authenticate.js";
import { env } from "../env.js";

export async function authRoutes(app: FastifyInstance): Promise<void> {
  // Limite mais agressivo nas rotas de credencial, contra força bruta.
  const credentialRateLimit = {
    config: { rateLimit: { max: 10, timeWindow: "1 minute" } },
  };

  /** POST /auth/signup — cria conta. */
  app.post("/signup", credentialRateLimit, async (request, reply) => {
    try {
      const { email, password, name } = signUpSchema.parse(request.body);

      const { data, error } = await supabaseAnon.auth.signUp({
        email,
        password,
        options: { data: name ? { name } : {} },
      });
      if (error) throw fromSupabaseAuthError(error, "signUp");
      if (!data.user) throw fromSupabaseAuthError({ message: "Falha ao criar usuário" }, "signUp");

      // Sem sessão => projeto exige confirmação de e-mail.
      if (!data.session) {
        return reply.status(201).send({
          user: toAuthUser(data.user),
          session: null,
          emailConfirmationRequired: true,
        });
      }
      return reply.status(201).send(buildAuthResponse(reply, data.session, data.user));
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** POST /auth/signin — login com e-mail e senha. */
  app.post("/signin", credentialRateLimit, async (request, reply) => {
    try {
      const { email, password } = signInSchema.parse(request.body);

      const { data, error } = await supabaseAnon.auth.signInWithPassword({ email, password });
      if (error) throw fromSupabaseAuthError(error, "signIn");
      if (!data.session) throw unauthorized("E-mail ou senha inválidos");

      return reply.send(buildAuthResponse(reply, data.session));
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /**
   * POST /auth/refresh — troca o refresh token do cookie por um novo par de tokens.
   * O front chama isso no boot e sempre que o access token expira.
   */
  app.post("/refresh", async (request, reply) => {
    try {
      const refreshToken = readRefreshCookie(request);
      if (!refreshToken) throw unauthorized("Sessão não encontrada");

      const { data, error } = await supabaseAnon.auth.refreshSession({
        refresh_token: refreshToken,
      });
      if (error || !data.session) {
        clearRefreshCookie(reply);
        throw fromSupabaseAuthError(error ?? { message: "sessão inválida" }, "refresh");
      }

      return reply.send(buildAuthResponse(reply, data.session));
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** POST /auth/signout — revoga a sessão no Supabase e apaga o cookie. */
  app.post("/signout", async (request, reply) => {
    try {
      const header = request.headers.authorization?.split(" ")[1];
      if (header) {
        // Revoga a sessão do token apresentado; falha aqui não deve impedir o logout local.
        await supabaseForToken(header)
          .auth.signOut()
          .catch((err) => request.log.warn({ err }, "signOut remoto falhou"));
      }
      clearRefreshCookie(reply);
      return reply.status(204).send();
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** GET /auth/me — usuário da sessão atual. */
  app.get("/me", { preHandler: authenticate }, async (request, reply) => {
    try {
      return reply.send({ user: requireUser(request) });
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /**
   * POST /auth/password/reset — dispara e-mail de recuperação.
   * Sempre responde 204, mesmo para e-mail inexistente, para não permitir
   * descobrir quais e-mails têm conta.
   */
  app.post("/password/reset", credentialRateLimit, async (request, reply) => {
    try {
      const { email } = requestPasswordResetSchema.parse(request.body);
      const { error } = await supabaseAnon.auth.resetPasswordForEmail(email, {
        redirectTo: env.PASSWORD_RESET_REDIRECT_URL,
      });
      if (error) request.log.warn({ err: error }, "resetPasswordForEmail falhou");
      return reply.status(204).send();
    } catch (err) {
      return sendError(reply, err);
    }
  });

  /** PATCH /auth/password — troca a senha do usuário autenticado. */
  app.patch("/password", { preHandler: authenticate }, async (request, reply) => {
    try {
      const { password } = updatePasswordSchema.parse(request.body);
      const token = request.accessToken;
      if (!token) throw unauthorized();

      const { data, error } = await supabaseForToken(token).auth.updateUser({ password });
      if (error) throw fromSupabaseAuthError(error, "update");
      if (!data.user) throw unauthorized();

      return reply.send({ user: toAuthUser(data.user) });
    } catch (err) {
      return sendError(reply, err);
    }
  });
}

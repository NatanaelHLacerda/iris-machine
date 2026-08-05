import Fastify, { type FastifyInstance } from "fastify";
import cookie from "@fastify/cookie";
import cors from "@fastify/cors";
import rateLimit from "@fastify/rate-limit";
import { allowedOrigins, isProd } from "./env.js";
import { sendError } from "./lib/errors.js";
import { authRoutes } from "./routes/auth.js";
import { agentRoutes } from "./routes/agents.js";

export async function buildApp(): Promise<FastifyInstance> {
  const app = Fastify({
    logger: isProd
      ? true
      : { transport: { target: "pino-pretty", options: { translateTime: "HH:MM:ss", ignore: "pid,hostname" } } },
    trustProxy: isProd,
  });

  await app.register(cors, {
    origin: allowedOrigins,
    credentials: true, // necessário para o cookie de refresh
  });
  await app.register(cookie);
  await app.register(rateLimit, { max: 120, timeWindow: "1 minute" });

  app.setErrorHandler((err, _request, reply) => sendError(reply, err));

  app.get("/health", async () => ({ status: "ok", uptime: process.uptime() }));

  await app.register(authRoutes, { prefix: "/auth" });
  await app.register(agentRoutes, { prefix: "/agents" });

  return app;
}

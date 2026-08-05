const API_URL = (import.meta.env.VITE_API_URL ?? "http://localhost:3333").replace(/\/$/, "");

export class ApiRequestError extends Error {
  constructor(
    readonly status: number,
    readonly code: string,
    message: string,
    readonly details?: unknown,
  ) {
    super(message);
    this.name = "ApiRequestError";
  }
}

/**
 * O access token vive apenas em memória — nunca em localStorage, para não ficar
 * exposto a XSS. A persistência da sessão vem do cookie httpOnly de refresh.
 */
let accessToken: string | null = null;
let onUnauthorized: (() => void) | null = null;

export function setAccessToken(token: string | null): void {
  accessToken = token;
}

export function getAccessToken(): string | null {
  return accessToken;
}

export function setUnauthorizedHandler(handler: (() => void) | null): void {
  onUnauthorized = handler;
}

interface RequestOptions extends Omit<RequestInit, "body"> {
  body?: unknown;
  /** Não tenta renovar a sessão em caso de 401 (usado pelo próprio refresh). */
  skipRefresh?: boolean;
}

let refreshInFlight: Promise<boolean> | null = null;

/** Renova o access token; chamadas simultâneas compartilham a mesma requisição. */
export function refreshSession(): Promise<boolean> {
  refreshInFlight ??= (async () => {
    try {
      const res = await fetch(`${API_URL}/auth/refresh`, {
        method: "POST",
        credentials: "include",
      });
      if (!res.ok) {
        accessToken = null;
        return false;
      }
      const data = (await res.json()) as { session?: { accessToken: string } };
      accessToken = data.session?.accessToken ?? null;
      return Boolean(accessToken);
    } catch {
      accessToken = null;
      return false;
    } finally {
      refreshInFlight = null;
    }
  })();
  return refreshInFlight;
}

export async function apiFetch<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { body, skipRefresh, headers, ...rest } = options;

  const doRequest = async (): Promise<Response> =>
    fetch(`${API_URL}${path}`, {
      ...rest,
      credentials: "include",
      headers: {
        ...(body !== undefined ? { "Content-Type": "application/json" } : {}),
        ...(accessToken ? { Authorization: `Bearer ${accessToken}` } : {}),
        ...headers,
      },
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });

  let response = await doRequest();

  // Access token expirado: renova uma vez e repete a requisição original.
  if (response.status === 401 && !skipRefresh) {
    const renewed = await refreshSession();
    if (renewed) {
      response = await doRequest();
    } else {
      onUnauthorized?.();
    }
  }

  if (response.status === 204) return undefined as T;

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    const error = (payload as { error?: { code: string; message: string; details?: unknown } })?.error;
    throw new ApiRequestError(
      response.status,
      error?.code ?? "unknown_error",
      error?.message ?? "Falha na comunicação com o servidor",
      error?.details,
    );
  }

  return payload as T;
}

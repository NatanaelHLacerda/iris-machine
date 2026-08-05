import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from "react";
import type { AuthUser, SignInInput, SignUpInput } from "@iris/shared";
import { refreshSession, setAccessToken, setUnauthorizedHandler } from "@/lib/api";
import { authApi } from "./auth-api";

interface AuthContextValue {
  user: AuthUser | null;
  /** true enquanto a sessão inicial ainda não foi resolvida. */
  loading: boolean;
  signIn: (input: SignInInput) => Promise<void>;
  signUp: (input: SignUpInput) => Promise<{ emailConfirmationRequired: boolean }>;
  signOut: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

/** Renova o token um pouco antes de expirar, para não perder requisições. */
const REFRESH_MARGIN_MS = 60_000;

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);
  const refreshTimer = useRef<number | null>(null);

  const clearRefreshTimer = useCallback(() => {
    if (refreshTimer.current !== null) {
      window.clearTimeout(refreshTimer.current);
      refreshTimer.current = null;
    }
  }, []);

  const scheduleRefresh = useCallback(
    (expiresAt: number | null) => {
      clearRefreshTimer();
      if (!expiresAt) return;
      const delay = expiresAt * 1000 - Date.now() - REFRESH_MARGIN_MS;
      refreshTimer.current = window.setTimeout(
        () => {
          void refreshSession().then((ok) => {
            if (!ok) setUser(null);
          });
        },
        Math.max(delay, 5_000),
      );
    },
    [clearRefreshTimer],
  );

  // Sessão inicial: o cookie httpOnly de refresh permite retomar o login após reload.
  useEffect(() => {
    let cancelled = false;

    (async () => {
      const renewed = await refreshSession();
      if (cancelled) return;
      if (!renewed) {
        setLoading(false);
        return;
      }
      try {
        const { user: me } = await authApi.me();
        if (!cancelled) setUser(me);
      } catch {
        if (!cancelled) setUser(null);
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  // Qualquer 401 irrecuperável derruba a sessão local.
  useEffect(() => {
    setUnauthorizedHandler(() => {
      setUser(null);
      setAccessToken(null);
      clearRefreshTimer();
    });
    return () => setUnauthorizedHandler(null);
  }, [clearRefreshTimer]);

  useEffect(() => clearRefreshTimer, [clearRefreshTimer]);

  const signIn = useCallback(
    async (input: SignInInput) => {
      const data = await authApi.signIn(input);
      setAccessToken(data.session?.accessToken ?? null);
      setUser(data.user);
      scheduleRefresh(data.session?.expiresAt ?? null);
    },
    [scheduleRefresh],
  );

  const signUp = useCallback(
    async (input: SignUpInput) => {
      const data = await authApi.signUp(input);
      if (data.session) {
        setAccessToken(data.session.accessToken);
        setUser(data.user);
        scheduleRefresh(data.session.expiresAt);
      }
      return { emailConfirmationRequired: Boolean(data.emailConfirmationRequired) };
    },
    [scheduleRefresh],
  );

  const signOut = useCallback(async () => {
    try {
      await authApi.signOut();
    } finally {
      setAccessToken(null);
      setUser(null);
      clearRefreshTimer();
    }
  }, [clearRefreshTimer]);

  const value = useMemo(
    () => ({ user, loading, signIn, signUp, signOut }),
    [user, loading, signIn, signUp, signOut],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth precisa estar dentro de <AuthProvider>");
  return ctx;
}

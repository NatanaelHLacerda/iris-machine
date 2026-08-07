import { useCallback, useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import type { Agent, AgentStatus, DashboardStats } from "@iris/shared";
import { agentsApi } from "@/features/agents/agents-api";
import { useAuth } from "@/features/auth/AuthContext";
import { AppSidebar } from "@/components/AppSidebar";
import { colors, fonts } from "@/styles/theme";

const statusLabels: Record<AgentStatus, { label: string; color: string }> = {
  online: { label: "Online", color: "#7CF2A6" },
  offline: { label: "Offline", color: "#7c8894" },
  busy: { label: "Ocupado", color: "#F9A8D0" },
  error: { label: "Erro", color: "#F2617A" },
};

function relativeTime(iso: string | null): string {
  if (!iso) return "—";
  const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60_000);
  if (minutes < 1) return "agora";
  if (minutes < 60) return `há ${minutes} min`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `há ${hours} h`;
  return `há ${Math.round(hours / 24)} d`;
}

function formatDuration(seconds: number | null): string {
  if (seconds === null) return "—";
  if (seconds < 60) return `${Math.round(seconds)}s`;
  const minutes = Math.floor(seconds / 60);
  const rest = Math.round(seconds % 60);
  return `${minutes}m ${rest}s`;
}

function StatCard({ label, value }: { label: string; value: string }) {
  return (
    <div
      style={{
        borderRadius: 14,
        border: `1px solid ${colors.border}`,
        background: colors.bgElevated,
        padding: 18,
      }}
    >
      <span style={{ fontFamily: fonts.mono, fontSize: 10.5, letterSpacing: "0.06em", color: colors.textDim }}>
        {label}
      </span>
      <div style={{ marginTop: 8 }}>
        <span style={{ fontSize: 26, fontWeight: 700, color: colors.text }}>{value}</span>
      </div>
    </div>
  );
}

function StatCardSkeleton() {
  return (
    <div style={{ borderRadius: 14, border: `1px solid ${colors.border}`, background: colors.bgElevated, padding: 18 }}>
      <div className="iris-skeleton" style={{ width: "60%", height: 10 }} />
      <div className="iris-skeleton" style={{ width: "40%", height: 26, marginTop: 10 }} />
    </div>
  );
}

export function DashboardPage() {
  const navigate = useNavigate();
  const { user, signOut } = useAuth();
  const [agents, setAgents] = useState<Agent[]>([]);
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    Promise.all([agentsApi.list(), agentsApi.stats()])
      .then(([{ agents: list }, { stats: s }]) => {
        if (cancelled) return;
        setAgents(list);
        setStats(s);
      })
      .catch(() => {
        if (!cancelled) setError("Não foi possível carregar os dados do painel.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => load(), [load]);

  return (
    <div
      style={{
        height: "100vh",
        width: "100%",
        background: colors.bg,
        color: colors.text,
        fontFamily: fonts.body,
        display: "grid",
        gridTemplateColumns: "240px 1fr",
        overflow: "hidden",
      }}
    >
      <AppSidebar
        showLogo
        links={[{ to: "/painel", icon: "◧", label: "Painel", active: true }]}
        userName={user?.name ?? user?.email ?? ""}
        userAvatar={user?.avatarUrl ?? "/uploads/761a440f4d38e77c845b67badf122797.jpg"}
        footerAction={{
          label: user?.role === "admin" ? "Admin" : "Sair",
          onClick: () => void signOut().then(() => navigate("/", { replace: true })),
        }}
      />

      <main style={{ overflowY: "auto", padding: "26px clamp(20px, 3vw, 40px) 40px" }}>
        <header style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 26 }}>
          <div>
            <h1 style={{ margin: 0, fontSize: 22, fontWeight: 700 }}>Painel de agentes</h1>
            <span style={{ fontFamily: fonts.mono, fontSize: 11.5, color: colors.textDim }}>
              VPS Hostinger · conexão estável
            </span>
          </div>
        </header>

        {error ? (
          <div
            role="alert"
            style={{
              marginBottom: 18,
              padding: "10px 14px",
              borderRadius: 8,
              border: "1px solid rgba(242,97,122,0.27)",
              background: "rgba(242,97,122,0.08)",
              color: colors.danger,
              fontSize: 13,
              display: "flex",
              alignItems: "center",
              justifyContent: "space-between",
              gap: 12,
            }}
          >
            <span>{error}</span>
            <button
              type="button"
              onClick={load}
              style={{
                background: "transparent",
                border: `1px solid ${colors.danger}`,
                borderRadius: 6,
                color: colors.danger,
                fontSize: 12,
                fontWeight: 600,
                padding: "5px 10px",
                cursor: "pointer",
                flex: "0 0 auto",
              }}
            >
              Tentar novamente
            </button>
          </div>
        ) : null}

        <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 14, marginBottom: 28 }}>
          {loading || !stats ? (
            <>
              <StatCardSkeleton />
              <StatCardSkeleton />
              <StatCardSkeleton />
            </>
          ) : (
            <>
              <StatCard label="AGENTES ATIVOS" value={String(stats.activeAgents)} />
              <StatCard label="CONVERSAS HOJE" value={String(stats.conversationsToday)} />
              <StatCard label="TEMPO MÉDIO DE RESPOSTA" value={formatDuration(stats.avgResponseSeconds)} />
            </>
          )}
        </div>

        <div
          style={{
            borderRadius: 16,
            border: `1px solid ${colors.border}`,
            background: "rgba(255,255,255,0.02)",
            overflow: "hidden",
          }}
        >
          <div
            style={{
              display: "flex",
              alignItems: "center",
              justifyContent: "space-between",
              padding: "16px 20px",
              borderBottom: `1px solid ${colors.border}`,
            }}
          >
            <span style={{ fontWeight: 600, fontSize: 15 }}>Meus agentes</span>
            <span style={{ fontFamily: fonts.mono, fontSize: 11, color: colors.textFaint }}>
              {loading ? "…" : `${agents.length} ativos`}
            </span>
          </div>

          <div
            style={{
              display: "grid",
              gridTemplateColumns: "2.2fr 1fr 1fr 1fr 0.6fr",
              padding: "10px 20px",
              fontFamily: fonts.mono,
              fontSize: 10.5,
              letterSpacing: "0.05em",
              color: colors.textFaint,
            }}
          >
            <span>AGENTE</span>
            <span>STATUS</span>
            <span>CONVERSAS</span>
            <span>ÚLTIMA ATIVIDADE</span>
            <span style={{ textAlign: "right" }}>CONFIG</span>
          </div>

          {loading
            ? [0, 1].map((i) => (
                <div key={i} style={{ padding: "13px 20px", borderTop: `1px solid rgba(255,255,255,0.06)` }}>
                  <div className="iris-skeleton" style={{ width: "40%", height: 16 }} />
                </div>
              ))
            : agents.map((agent) => {
                const status = statusLabels[agent.status];
                return (
                  <Link
                    key={agent.id}
                    to={`/agentes/${agent.id}/conversa`}
                    className="iris-row"
                    style={{
                      display: "grid",
                      gridTemplateColumns: "2.2fr 1fr 1fr 1fr 0.6fr",
                      alignItems: "center",
                      padding: "13px 20px",
                      borderTop: "1px solid rgba(255,255,255,0.06)",
                      cursor: "pointer",
                      color: "inherit",
                    }}
                  >
                    <div style={{ display: "flex", alignItems: "center", gap: 12, minWidth: 0 }}>
                      <div style={{ width: 34, height: 34, borderRadius: 9, overflow: "hidden", flex: "0 0 auto" }}>
                        {agent.avatarUrl ? (
                          <img
                            src={agent.avatarUrl}
                            alt={agent.name}
                            style={{ width: "100%", height: "100%", objectFit: "cover" }}
                          />
                        ) : null}
                      </div>
                      <div style={{ display: "flex", flexDirection: "column", minWidth: 0 }}>
                        <span style={{ fontSize: 13.5, fontWeight: 600, color: colors.text }}>{agent.name}</span>
                        <span
                          style={{
                            fontSize: 11.5,
                            color: colors.textDim,
                            overflow: "hidden",
                            textOverflow: "ellipsis",
                            whiteSpace: "nowrap",
                          }}
                        >
                          {agent.role}
                        </span>
                      </div>
                    </div>
                    <span style={{ display: "flex", alignItems: "center", gap: 6, fontSize: 12.5, color: status.color }}>
                      <span
                        style={{
                          width: 6,
                          height: 6,
                          borderRadius: "50%",
                          background: status.color,
                          boxShadow: `0 0 6px ${status.color}`,
                        }}
                      />
                      {status.label}
                    </span>
                    <span style={{ fontSize: 13, color: "#d6cadc" }}>{agent.conversations}</span>
                    <span style={{ fontFamily: fonts.mono, fontSize: 11.5, color: colors.textDim }}>
                      {relativeTime(agent.lastRunAt)}
                    </span>
                    <span
                      role="button"
                      tabIndex={0}
                      onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        navigate(`/agentes/${agent.id}/configuracao`);
                      }}
                      onKeyDown={(e) => {
                        if (e.key === "Enter") {
                          e.preventDefault();
                          navigate(`/agentes/${agent.id}/configuracao`);
                        }
                      }}
                      style={{ textAlign: "right", color: colors.textDim, cursor: "pointer", fontSize: 15 }}
                    >
                      ⚙
                    </span>
                  </Link>
                );
              })}
        </div>
      </main>
    </div>
  );
}

import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import type { Agent, AgentStatus } from "@iris/shared";
import { agentsApi } from "@/features/agents/agents-api";
import { useAuth } from "@/features/auth/AuthContext";
import { FullScreenLoader } from "@/components/FullScreenLoader";

const statusLabels: Record<AgentStatus, { label: string; color: string }> = {
  online: { label: "Online", color: "#7CF2A6" },
  offline: { label: "Offline", color: "#7c8894" },
  busy: { label: "Ocupado", color: "#F9A8D0" },
  error: { label: "Erro", color: "#F2617A" },
};

const stats = [
  { label: "AGENTES ATIVOS", value: "4", delta: "+1", deltaColor: "#7CF2A6" },
  { label: "CONVERSAS HOJE", value: "128", delta: "+12%", deltaColor: "#7CF2A6" },
  { label: "TEMPO MÉDIO", value: "2m 40s", delta: "-8%", deltaColor: "#7CF2A6" },
  { label: "USO DA VPS", value: "63%", delta: "+4%", deltaColor: "#F9A8D0" },
];

const navItems = [{ icon: "◧", label: "Painel", active: true }];

function relativeTime(iso: string | null): string {
  if (!iso) return "—";
  const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60_000);
  if (minutes < 1) return "agora";
  if (minutes < 60) return `há ${minutes} min`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `há ${hours} h`;
  return `há ${Math.round(hours / 24)} d`;
}

export function DashboardPage() {
  const navigate = useNavigate();
  const { user, signOut } = useAuth();
  const [agents, setAgents] = useState<Agent[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    agentsApi
      .list()
      .then(({ agents: list }) => {
        if (!cancelled) setAgents(list);
      })
      .catch(() => {
        if (!cancelled) setError("Não foi possível carregar os agentes.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  if (loading) return <FullScreenLoader label="Carregando agentes…" />;

  return (
    <div
      style={{
        height: "100vh",
        width: "100%",
        background: "#0B0910",
        color: "#F4EEF6",
        fontFamily: "'Space Grotesk', sans-serif",
        display: "grid",
        gridTemplateColumns: "240px 1fr",
        overflow: "hidden",
      }}
    >
      <aside
        style={{
          borderRight: "1px solid rgba(255,255,255,0.08)",
          display: "flex",
          flexDirection: "column",
          padding: "20px 14px",
        }}
      >
        <div style={{ display: "flex", alignItems: "center", gap: 10, padding: "8px 10px 24px" }}>
          <span
            style={{
              fontFamily: "'Orbitron', sans-serif",
              fontWeight: 700,
              fontSize: 15,
              letterSpacing: "0.2em",
              color: "#F4EEF6",
            }}
          >
            IRIS
          </span>
        </div>

        <nav style={{ display: "flex", flexDirection: "column", gap: 3 }}>
          {navItems.map((item) => (
            <div
              key={item.label}
              className="iris-nav-item"
              style={{
                display: "flex",
                alignItems: "center",
                gap: 11,
                padding: "10px 12px",
                borderRadius: 9,
                cursor: "pointer",
                background: item.active ? "rgba(242,73,160,0.12)" : "transparent",
              }}
            >
              <span
                style={{ fontSize: 15, width: 18, textAlign: "center", color: item.active ? "#F249A0" : "#b0a2ba" }}
              >
                {item.icon}
              </span>
              <span style={{ fontSize: 13.5, fontWeight: 500, color: item.active ? "#F249A0" : "#b0a2ba" }}>
                {item.label}
              </span>
            </div>
          ))}
        </nav>

        <div
          style={{
            marginTop: "auto",
            display: "flex",
            alignItems: "center",
            gap: 10,
            padding: "10px 12px",
            borderRadius: 10,
            background: "rgba(255,255,255,0.04)",
          }}
        >
          <div style={{ width: 30, height: 30, borderRadius: "50%", overflow: "hidden", flex: "0 0 auto" }}>
            <img
              src={user?.avatarUrl ?? "/uploads/761a440f4d38e77c845b67badf122797.jpg"}
              alt="usuário"
              style={{ width: "100%", height: "100%", objectFit: "cover" }}
            />
          </div>
          <div style={{ display: "flex", flexDirection: "column", minWidth: 0 }}>
            <span
              style={{
                fontSize: 12.5,
                fontWeight: 600,
                color: "#F4EEF6",
                overflow: "hidden",
                textOverflow: "ellipsis",
                whiteSpace: "nowrap",
              }}
            >
              {user?.name ?? user?.email}
            </span>
            <button
              type="button"
              onClick={() => void signOut().then(() => navigate("/", { replace: true }))}
              style={{
                background: "transparent",
                border: "none",
                padding: 0,
                textAlign: "left",
                fontSize: 10.5,
                color: "#7c8894",
                cursor: "pointer",
              }}
            >
              {user?.role === "admin" ? "Admin" : "Sair"}
            </button>
          </div>
        </div>
      </aside>

      <main style={{ overflowY: "auto", padding: "26px clamp(20px, 3vw, 40px) 40px" }}>
        <header style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 26 }}>
          <div>
            <h1 style={{ margin: 0, fontSize: 22, fontWeight: 700 }}>Painel de agentes</h1>
            <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: "#7c8894" }}>
              VPS Hostinger · conexão estável
            </span>
          </div>
          <button
            type="button"
            className="iris-button-primary"
            style={{
              padding: "10px 18px",
              borderRadius: 9,
              border: "none",
              background: "#F249A0",
              color: "#fff",
              fontFamily: "'Space Grotesk', sans-serif",
              fontWeight: 600,
              fontSize: 13.5,
              cursor: "pointer",
            }}
          >
            + Novo agente
          </button>
        </header>

        {error ? (
          <div
            style={{
              marginBottom: 18,
              padding: "10px 14px",
              borderRadius: 8,
              border: "1px solid rgba(242,97,122,0.27)",
              background: "rgba(242,97,122,0.08)",
              color: "#F2617A",
              fontSize: 13,
            }}
          >
            {error}
          </div>
        ) : null}

        <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 14, marginBottom: 28 }}>
          {stats.map((stat) => (
            <div
              key={stat.label}
              style={{
                borderRadius: 14,
                border: "1px solid rgba(255,255,255,0.08)",
                background: "rgba(255,255,255,0.03)",
                padding: 18,
              }}
            >
              <span
                style={{
                  fontFamily: "'IBM Plex Mono', monospace",
                  fontSize: 10.5,
                  letterSpacing: "0.06em",
                  color: "#7c8894",
                }}
              >
                {stat.label}
              </span>
              <div style={{ display: "flex", alignItems: "baseline", gap: 8, marginTop: 8 }}>
                <span style={{ fontSize: 26, fontWeight: 700, color: "#F4EEF6" }}>{stat.value}</span>
                <span style={{ fontSize: 12, fontWeight: 600, color: stat.deltaColor }}>{stat.delta}</span>
              </div>
            </div>
          ))}
        </div>

        <div
          style={{
            borderRadius: 16,
            border: "1px solid rgba(255,255,255,0.08)",
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
              borderBottom: "1px solid rgba(255,255,255,0.08)",
            }}
          >
            <span style={{ fontWeight: 600, fontSize: 15 }}>Meus agentes</span>
            <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: "#7c8894" }}>
              {agents.length} ativos
            </span>
          </div>

          <div
            style={{
              display: "grid",
              gridTemplateColumns: "2.2fr 1fr 1fr 1fr 0.6fr",
              padding: "10px 20px",
              fontFamily: "'IBM Plex Mono', monospace",
              fontSize: 10.5,
              letterSpacing: "0.05em",
              color: "#6f7a85",
            }}
          >
            <span>AGENTE</span>
            <span>STATUS</span>
            <span>CONVERSAS</span>
            <span>ÚLTIMA ATIVIDADE</span>
            <span style={{ textAlign: "right" }}>CONFIG</span>
          </div>

          {agents.map((agent) => {
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
                    <span style={{ fontSize: 13.5, fontWeight: 600, color: "#F4EEF6" }}>{agent.name}</span>
                    <span
                      style={{
                        fontSize: 11.5,
                        color: "#7c8894",
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
                <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: "#7c8894" }}>
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
                  style={{ textAlign: "right", color: "#7c8894", cursor: "pointer", fontSize: 15 }}
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

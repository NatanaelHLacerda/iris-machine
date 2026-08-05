import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import type { Agent, AgentStatus } from "@iris/shared";
import { agentsApi } from "@/features/agents/agents-api";
import { Alert, StatusDot } from "@/components/ui";
import { FullScreenLoader } from "@/components/FullScreenLoader";
import { colors, fonts, radii } from "@/styles/theme";

const statusLabels: Record<AgentStatus, { label: string; color: string }> = {
  online: { label: "Online", color: colors.success },
  offline: { label: "Offline", color: colors.textDim },
  busy: { label: "Ocupado", color: colors.accentSoft },
  error: { label: "Erro", color: colors.danger },
};

const stats = [
  { label: "AGENTES ATIVOS", value: "4", delta: "+1", deltaColor: colors.success },
  { label: "CONVERSAS HOJE", value: "128", delta: "+12%", deltaColor: colors.success },
  { label: "TEMPO MÉDIO", value: "2m 40s", delta: "-8%", deltaColor: colors.success },
  { label: "USO DA VPS", value: "63%", delta: "+4%", deltaColor: colors.accentSoft },
];

function relativeTime(iso: string | null): string {
  if (!iso) return "—";
  const diffMs = Date.now() - new Date(iso).getTime();
  const minutes = Math.round(diffMs / 60_000);
  if (minutes < 1) return "agora";
  if (minutes < 60) return `há ${minutes} min`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `há ${hours} h`;
  return `há ${Math.round(hours / 24)} d`;
}

export function DashboardPage() {
  const navigate = useNavigate();
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
    <div style={{ padding: "26px clamp(20px, 3vw, 40px) 40px" }}>
      <header
        style={{
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          marginBottom: 26,
          gap: 16,
        }}
      >
        <div>
          <h1 style={{ margin: 0, fontSize: 22, fontWeight: 700 }}>Painel de agentes</h1>
          <span style={{ fontFamily: fonts.mono, fontSize: 11.5, color: colors.textDim }}>
            VPS Hostinger · conexão estável
          </span>
        </div>
      </header>

      {error ? <Alert>{error}</Alert> : null}

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(180px, 1fr))", gap: 14, margin: "18px 0 28px" }}>
        {stats.map((stat) => (
          <div
            key={stat.label}
            style={{
              borderRadius: radii.lg,
              border: `1px solid ${colors.border}`,
              background: "rgba(255,255,255,0.03)",
              padding: 18,
            }}
          >
            <span style={{ fontFamily: fonts.mono, fontSize: 10.5, letterSpacing: "0.06em", color: colors.textDim }}>
              {stat.label}
            </span>
            <div style={{ display: "flex", alignItems: "baseline", gap: 8, marginTop: 8 }}>
              <span style={{ fontSize: 26, fontWeight: 700 }}>{stat.value}</span>
              <span style={{ fontSize: 12, fontWeight: 600, color: stat.deltaColor }}>{stat.delta}</span>
            </div>
          </div>
        ))}
      </div>

      <div
        style={{
          borderRadius: radii.xl,
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
          <span style={{ fontFamily: fonts.mono, fontSize: 11, color: colors.textDim }}>
            {agents.filter((a) => a.active).length} ativos
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
          <span>TAREFAS</span>
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
                borderTop: `1px solid rgba(255,255,255,0.06)`,
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
                  <span style={{ fontSize: 13.5, fontWeight: 600 }}>{agent.name}</span>
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
                <StatusDot color={status.color} />
                {status.label}
              </span>
              <span style={{ fontSize: 13, color: "#d6cadc" }}>{agent.tasksDone}</span>
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
    </div>
  );
}

import { useEffect, useState } from "react";
import { Navigate, useLocation, useNavigate } from "react-router-dom";
import { useAuth } from "@/features/auth/AuthContext";
import { AuthForm } from "@/features/auth/AuthForm";
import { FullScreenLoader } from "@/components/FullScreenLoader";
import { colors, fonts, radii } from "@/styles/theme";

const tiles = [
  { label: "AGENTES", value: "4" },
  { label: "CONVERSAS HOJE", value: "128" },
  { label: "TEMPO MÉDIO", value: "2m 40s" },
  { label: "USO DA VPS", value: "63%" },
];

export function LandingPage() {
  const { user, loading } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [showAuth, setShowAuth] = useState(false);

  const redirectTo = (location.state as { from?: string } | null)?.from ?? "/painel";

  useEffect(() => {
    if (location.state && (location.state as { from?: string }).from) setShowAuth(true);
  }, [location.state]);

  if (loading) return <FullScreenLoader />;
  if (user) return <Navigate to={redirectTo} replace />;

  return (
    <div
      style={{
        minHeight: "100vh",
        background: "radial-gradient(130% 120% at 85% 8%, #1a1122 0%, #0B0910 58%)",
        color: colors.text,
        display: "flex",
        flexDirection: "column",
        padding: "clamp(24px, 4vw, 56px)",
      }}
    >
      <header style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 16, flexWrap: "wrap" }}>
        <span
          style={{
            fontFamily: fonts.display,
            fontWeight: 700,
            fontSize: 22,
            letterSpacing: "0.28em",
            textTransform: "uppercase",
          }}
        >
          Iris Machine
        </span>
        <button
          type="button"
          onClick={() => setShowAuth((v) => !v)}
          className="iris-button-primary"
          style={{
            padding: "9px 20px",
            borderRadius: radii.sm,
            border: "none",
            background: colors.accent,
            color: "#fff",
            fontWeight: 600,
            fontSize: 13,
            cursor: "pointer",
          }}
        >
          {showAuth ? "Fechar" : "Entrar"}
        </button>
      </header>

      <main
        style={{
          flex: 1,
          display: "grid",
          gridTemplateColumns: "minmax(320px, 1fr) minmax(320px, 1.05fr)",
          gap: "clamp(40px, 6vw, 88px)",
          alignItems: "center",
          padding: "48px clamp(0px, 2vw, 40px)",
        }}
      >
        <div style={{ display: "flex", flexDirection: "column", gap: 22, animation: "iris-float-up 0.7s ease both" }}>
          <h1
            style={{
              fontWeight: 700,
              fontSize: "clamp(34px, 4.4vw, 62px)",
              lineHeight: 1.03,
              letterSpacing: "-0.03em",
              margin: 0,
            }}
          >
            Crie, configure e comande seus agentes <span style={{ color: colors.accent }}>remotamente</span>.
          </h1>
          <p style={{ fontSize: "clamp(15px, 1.2vw, 19px)", lineHeight: 1.55, color: colors.textMuted, maxWidth: "30em", margin: 0 }}>
            Uma plataforma para orquestrar agentes de IA com o Hermes AI. Configure agentes hospedados na sua VPS,
            comunique-se com eles à distância e receba resultados — tudo em um só lugar.
          </p>
        </div>

        <div style={{ justifySelf: "center", width: "100%", maxWidth: 460 }}>
          {showAuth ? (
            <div
              style={{
                borderRadius: radii.xl,
                border: `1px solid ${colors.border}`,
                background: "rgba(255,255,255,0.03)",
                padding: 26,
                display: "flex",
                flexDirection: "column",
                gap: 18,
                animation: "iris-fade-up 0.3s ease both",
              }}
            >
              <div style={{ display: "flex", flexDirection: "column", gap: 4 }}>
                <span style={{ fontFamily: fonts.mono, fontSize: 11, letterSpacing: "0.08em", color: colors.accent }}>
                  ACESSO
                </span>
                <span style={{ fontSize: 18, fontWeight: 600 }}>Entre na sua conta</span>
              </div>
              <AuthForm onSuccess={() => navigate(redirectTo, { replace: true })} />
            </div>
          ) : (
            <div style={{ display: "grid", gridTemplateColumns: "repeat(2, 1fr)", gap: 16 }}>
              {tiles.map((tile) => (
                <div
                  key={tile.label}
                  className="iris-agent-card"
                  style={{
                    borderRadius: radii.lg,
                    border: `1px solid ${colors.border}`,
                    background: "rgba(255,255,255,0.03)",
                    padding: 20,
                    minHeight: 116,
                    display: "flex",
                    flexDirection: "column",
                    justifyContent: "space-between",
                  }}
                >
                  <span style={{ fontFamily: fonts.mono, fontSize: 10.5, letterSpacing: "0.06em", color: colors.textDim }}>
                    {tile.label}
                  </span>
                  <span style={{ fontSize: 26, fontWeight: 700 }}>{tile.value}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      </main>
    </div>
  );
}

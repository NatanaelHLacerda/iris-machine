import { useState, type FormEvent } from "react";
import { Navigate, useLocation, useNavigate } from "react-router-dom";
import { signInSchema } from "@iris/shared";
import { ZodError } from "zod";
import { ApiRequestError } from "@/lib/api";
import { useAuth } from "@/features/auth/AuthContext";
import { FullScreenLoader } from "@/components/FullScreenLoader";

/** Mosaico do hero — mesmas imagens, colunas e linhas do protótipo. */
const tiles = [
  { label: "Painel de agentes", col: "1 / 3", row: "1 / 5", img: "/uploads/4976f1a9174913d920679247dc07c43c.jpg" },
  { label: "Configuração", col: "3 / 5", row: "1 / 3", img: "/uploads/dcd4b83b6469fd536292eee1361b957e.jpg" },
  { label: "Comunicação remota", col: "3 / 5", row: "3 / 5", img: "/uploads/8af21dc5e6a296b59cdecd7ada7e038a.jpg" },
];

const capabilities = [
  "GitHub",
  "Claude Opus",
  "Análise de código",
  "Auto-correção",
  "Convenções de projeto",
  "Validação contínua",
];

export function LandingPage() {
  const { user, loading, signIn, signUp } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [mode, setMode] = useState<"signin" | "signup">("signin");
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const redirectTo = (location.state as { from?: string } | null)?.from ?? "/painel";

  if (loading) return <FullScreenLoader />;
  if (user) return <Navigate to={redirectTo} replace />;

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setNotice(null);
    setSubmitting(true);
    try {
      if (mode === "signin") {
        await signIn(signInSchema.parse({ email, password }));
        navigate(redirectTo, { replace: true });
      } else {
        const { emailConfirmationRequired } = await signUp({ email, password });
        if (emailConfirmationRequired) {
          setNotice("Conta criada. Confirme o e-mail para entrar.");
          setMode("signin");
        } else {
          navigate(redirectTo, { replace: true });
        }
      }
    } catch (err) {
      if (err instanceof ZodError) setError(err.issues[0]?.message ?? "Dados inválidos");
      else if (err instanceof ApiRequestError) setError(err.message);
      else setError("Não foi possível entrar.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div style={{ background: "#0B0910" }}>
      <div
        style={{
          minHeight: "100vh",
          width: "100%",
          background: "radial-gradient(130% 120% at 85% 8%, #1a1122 0%, #0B0910 58%)",
          color: "#F4EEF6",
          fontFamily: "'Space Grotesk', sans-serif",
          display: "flex",
          flexDirection: "column",
          padding: "clamp(24px, 4vw, 56px)",
          position: "relative",
        }}
      >
        <header style={{ display: "flex", alignItems: "center", justifyContent: "space-between", flex: "0 0 auto" }}>
          <div style={{ display: "flex", alignItems: "center", gap: 11 }}>
            <span
              style={{
                fontFamily: "'Orbitron', sans-serif",
                fontWeight: 700,
                fontSize: 22,
                letterSpacing: "0.28em",
                textTransform: "uppercase",
                color: "#F4EEF6",
              }}
            >
              IRIS MACHINE
            </span>
          </div>

          <div style={{ display: "flex", flexDirection: "column", alignItems: "flex-end", gap: 6 }}>
            <form onSubmit={handleSubmit} style={{ display: "flex", alignItems: "center", gap: 8 }}>
              <input
                type="email"
                placeholder="E-mail"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                autoComplete="email"
                style={{
                  width: 150,
                  padding: "8px 12px",
                  borderRadius: 8,
                  border: "1px solid rgba(255,255,255,0.14)",
                  background: "rgba(255,255,255,0.04)",
                  color: "#F4EEF6",
                  fontFamily: "'Space Grotesk', sans-serif",
                  fontSize: 13,
                  outline: "none",
                }}
              />
              <input
                type="password"
                placeholder="Senha"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                autoComplete={mode === "signup" ? "new-password" : "current-password"}
                style={{
                  width: 120,
                  padding: "8px 12px",
                  borderRadius: 8,
                  border: "1px solid rgba(255,255,255,0.14)",
                  background: "rgba(255,255,255,0.04)",
                  color: "#F4EEF6",
                  fontFamily: "'Space Grotesk', sans-serif",
                  fontSize: 13,
                  outline: "none",
                }}
              />
              <button
                type="submit"
                disabled={submitting}
                className="iris-button-primary"
                style={{
                  padding: "8px 18px",
                  borderRadius: 8,
                  border: "none",
                  background: "#F249A0",
                  color: "#ffffff",
                  fontFamily: "'Space Grotesk', sans-serif",
                  fontWeight: 600,
                  fontSize: 13,
                  cursor: "pointer",
                }}
              >
                {submitting ? "…" : mode === "signin" ? "Entrar" : "Criar"}
              </button>
            </form>

            <div style={{ display: "flex", alignItems: "center", gap: 10, fontSize: 11, height: 14 }}>
              {error ? <span style={{ color: "#F2617A" }}>{error}</span> : null}
              {notice ? <span style={{ color: "#7CF2A6" }}>{notice}</span> : null}
              <button
                type="button"
                onClick={() => {
                  setMode(mode === "signin" ? "signup" : "signin");
                  setError(null);
                  setNotice(null);
                }}
                style={{
                  background: "transparent",
                  border: "none",
                  padding: 0,
                  color: "#7c8894",
                  fontSize: 11,
                  fontFamily: "'IBM Plex Mono', monospace",
                  cursor: "pointer",
                }}
              >
                {mode === "signin" ? "criar conta" : "já tenho conta"}
              </button>
            </div>
          </div>
        </header>

        <main
          style={{
            flex: "1 1 auto",
            display: "grid",
            gridTemplateColumns: "1fr 1.12fr",
            gap: "clamp(48px, 6vw, 96px)",
            alignItems: "center",
            minHeight: 0,
            padding: "0 clamp(8px, 2vw, 40px)",
          }}
        >
          <div
            style={{
              display: "flex",
              flexDirection: "column",
              gap: "clamp(14px, 2.4vh, 26px)",
              animation: "iris-float-up 0.7s ease both",
            }}
          >
            <h1
              style={{
                fontFamily: "'Space Grotesk', sans-serif",
                fontWeight: 700,
                fontSize: "clamp(38px, 4.6vw, 68px)",
                lineHeight: 1.02,
                letterSpacing: "-0.03em",
                margin: 0,
                textWrap: "balance",
              }}
            >
              Crie, configure e comande seus agentes <span style={{ color: "#F249A0" }}>remotamente</span>.
            </h1>
            <p
              style={{
                fontSize: "clamp(16px, 1.2vw, 19px)",
                lineHeight: 1.55,
                color: "#b0a2ba",
                maxWidth: "30em",
                margin: 0,
                textWrap: "pretty",
              }}
            >
              Uma plataforma para orquestrar agentes de IA com o Hermes AI. Configure agentes hospedados na sua VPS,
              comunique-se com eles à distância e receba resultados — tudo em um só lugar.
            </p>
            <div style={{ display: "flex", gap: 14, alignItems: "center", marginTop: 4 }}>
              <a
                href="#saiba-mais"
                className="iris-button-primary"
                style={{
                  display: "inline-flex",
                  alignItems: "center",
                  gap: 9,
                  padding: "clamp(11px, 1.6vh, 14px) 24px",
                  borderRadius: 11,
                  background: "#F249A0",
                  color: "#ffffff",
                  fontFamily: "'Space Grotesk', sans-serif",
                  fontWeight: 600,
                  fontSize: 14,
                  letterSpacing: "0.12em",
                  textTransform: "uppercase",
                }}
              >
                Saiba mais
                <span style={{ fontSize: 17 }}>→</span>
              </a>
            </div>
          </div>

          <div
            style={{
              display: "grid",
              gridTemplateColumns: "repeat(4, 1fr)",
              gridTemplateRows: "repeat(4, 1fr)",
              gap: 18,
              height: "min(72vh, 560px)",
              width: "100%",
              maxWidth: 540,
              justifySelf: "center",
              animation: "iris-float-up 0.9s ease both",
            }}
          >
            {tiles.map((tile) => (
              <div
                key={tile.label}
                style={{
                  position: "relative",
                  borderRadius: 18,
                  overflow: "hidden",
                  border: "1px solid rgba(242,73,160,0.12)",
                  background: "rgba(123,108,246,0.05)",
                  backdropFilter: "blur(14px)",
                  gridColumn: tile.col,
                  gridRow: tile.row,
                  boxShadow: "0 24px 60px -32px #000, inset 0 1px 0 rgba(255,255,255,0.05)",
                }}
              >
                <img
                  src={tile.img}
                  alt={tile.label}
                  style={{
                    position: "absolute",
                    inset: 0,
                    width: "100%",
                    height: "100%",
                    objectFit: "cover",
                    objectPosition: "center",
                  }}
                />
              </div>
            ))}
          </div>
        </main>
      </div>

      <div
        style={{
          width: "100%",
          height: 1,
          background:
            "linear-gradient(90deg, transparent, rgba(242,73,160,0.28), rgba(123,108,246,0.2), transparent)",
        }}
      />

      <div
        id="saiba-mais"
        style={{
          width: "100%",
          position: "relative",
          background: "#0B0910",
          padding: "clamp(80px, 10vw, 140px) clamp(24px, 4vw, 56px)",
          display: "grid",
          gridTemplateColumns: "2fr 3fr",
          gap: "clamp(56px, 8vw, 88px)",
          alignItems: "center",
          overflow: "hidden",
        }}
      >
        <div
          style={{
            position: "absolute",
            inset: 0,
            pointerEvents: "none",
            opacity: 0.35,
            background:
              "radial-gradient(60% 55% at 22% 45%, rgba(123,108,246,0.10) 0%, transparent 70%), radial-gradient(50% 50% at 78% 60%, rgba(242,73,160,0.08) 0%, transparent 70%)",
          }}
        />
        <div
          style={{
            position: "absolute",
            inset: 0,
            pointerEvents: "none",
            opacity: 0.04,
            backgroundImage:
              "linear-gradient(rgba(255,255,255,0.7) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.7) 1px, transparent 1px)",
            backgroundSize: "56px 56px",
            maskImage: "radial-gradient(70% 60% at 50% 50%, #000 0%, transparent 80%)",
            WebkitMaskImage: "radial-gradient(70% 60% at 50% 50%, #000 0%, transparent 80%)",
          }}
        />

        <div
          className="holo-wrap"
          style={{ position: "relative", display: "flex", alignItems: "center", justifyContent: "center", perspective: 1200 }}
        >
          <div
            className="holo-glow"
            style={{
              position: "absolute",
              width: "112%",
              height: "108%",
              pointerEvents: "none",
              opacity: 0.75,
              transition: "opacity .5s ease",
              background:
                "radial-gradient(30% 30% at 26% 24%, rgba(96,196,255,0.32) 0%, transparent 70%), radial-gradient(34% 34% at 76% 66%, rgba(242,73,160,0.32) 0%, transparent 70%), radial-gradient(26% 26% at 60% 46%, rgba(196,120,70,0.20) 0%, transparent 70%), radial-gradient(30% 30% at 60% 90%, rgba(255,150,60,0.22) 0%, transparent 70%)",
              filter: "blur(20px)",
            }}
          />
          <div className="iris-reveal-img" style={{ position: "relative", width: "100%", maxWidth: 380 }}>
            <div
              className="holo"
              style={{
                position: "relative",
                borderRadius: 22,
                overflow: "hidden",
                boxShadow: "0 28px 64px -28px rgba(0,0,0,0.65), 0 0 40px -14px rgba(242,73,160,0.26)",
              }}
            >
              <div style={{ animation: "iris-breathe 9s ease-in-out infinite" }}>
                <img
                  src="/uploads/761a440f4d38e77c845b67badf122797.jpg"
                  alt="Jimmy"
                  style={{ width: "100%", height: "auto", display: "block" }}
                />
              </div>
              <div
                style={{
                  position: "absolute",
                  inset: 0,
                  pointerEvents: "none",
                  background:
                    "linear-gradient(128deg, rgba(255,255,255,0.20) 0%, rgba(255,255,255,0.05) 18%, transparent 34%)",
                }}
              />
              <div
                style={{
                  position: "absolute",
                  inset: 0,
                  pointerEvents: "none",
                  boxShadow: "inset 0 1px 0 rgba(255,255,255,0.14)",
                }}
              />
            </div>
          </div>
        </div>

        <div style={{ position: "relative", display: "flex", flexDirection: "column", gap: 20 }}>
          <div className="iris-reveal" style={{ display: "flex", alignItems: "center", gap: 8, animationDelay: ".05s" }}>
            <span
              style={{
                width: 6,
                height: 6,
                borderRadius: "50%",
                background: "#7CF2A6",
                boxShadow: "0 0 6px #7CF2A6",
              }}
            />
            <span
              style={{
                fontFamily: "'IBM Plex Mono', monospace",
                fontSize: 10.5,
                letterSpacing: "0.12em",
                color: "#7c8894",
              }}
            >
              ONLINE
            </span>
          </div>

          <div
            className="iris-reveal"
            style={{ display: "flex", flexDirection: "column", gap: 10, animationDelay: ".12s" }}
          >
            <h2
              style={{
                fontFamily: "'Space Grotesk', sans-serif",
                fontWeight: 700,
                fontSize: "clamp(30px, 3.2vw, 42px)",
                letterSpacing: "-0.02em",
                margin: 0,
                color: "#F4EEF6",
              }}
            >
              Jimmy
            </h2>
            <span
              style={{
                fontFamily: "'Space Grotesk', sans-serif",
                fontWeight: 500,
                fontSize: "clamp(19px, 1.7vw, 24px)",
                lineHeight: 1.3,
                color: "#c9b6d4",
                maxWidth: "22em",
              }}
            >
              Especialista em conversão e portabilidade de código entre plataformas.
            </span>
          </div>

          <p
            className="iris-reveal"
            style={{
              fontFamily: "'Space Grotesk', sans-serif",
              fontSize: 15.5,
              lineHeight: 1.6,
              color: "#9d90a8",
              margin: 0,
              maxWidth: "34em",
              animationDelay: ".2s",
            }}
          >
            Analisa projetos em diferentes linguagens e frameworks, e reconstrói cada tela ou componente na plataforma
            de destino — pronto para integrar, já validado e revisado.
          </p>

          <div
            className="iris-reveal"
            style={{ display: "flex", flexDirection: "column", gap: 10, marginTop: 4, animationDelay: ".28s" }}
          >
            <span
              style={{
                fontFamily: "'IBM Plex Mono', monospace",
                fontSize: 10,
                letterSpacing: "0.16em",
                color: "#6f7a85",
              }}
            >
              CAPACIDADES
            </span>
            <div style={{ display: "flex", flexWrap: "wrap", gap: 8 }}>
              {capabilities.map((cap) => (
                <span
                  key={cap}
                  style={{
                    fontFamily: "'IBM Plex Mono', monospace",
                    fontSize: 11,
                    letterSpacing: "0.04em",
                    padding: "6px 12px",
                    borderRadius: 999,
                    border: "1px solid rgba(242,73,160,0.2)",
                    color: "#b5a3c0",
                  }}
                >
                  {cap}
                </span>
              ))}
            </div>
          </div>

          <div className="iris-reveal" style={{ display: "flex", gap: 12, marginTop: 10, animationDelay: ".36s" }}>
            <a
              href="#conhecer"
              className="iris-button-primary"
              style={{
                padding: "12px 22px",
                borderRadius: 10,
                background: "#F249A0",
                color: "#ffffff",
                fontFamily: "'Space Grotesk', sans-serif",
                fontWeight: 600,
                fontSize: 14,
              }}
            >
              Conhecer agente
            </a>
            <a
              href="#capacidades"
              style={{
                padding: "12px 22px",
                borderRadius: 10,
                border: "1px solid rgba(255,255,255,0.14)",
                color: "#F4EEF6",
                fontFamily: "'Space Grotesk', sans-serif",
                fontWeight: 600,
                fontSize: 14,
              }}
            >
              Ver capacidades
            </a>
          </div>
        </div>
      </div>

      <footer
        style={{
          flex: "0 0 auto",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          fontFamily: "'IBM Plex Mono', monospace",
          fontSize: 11.5,
          color: "#59636d",
          letterSpacing: "0.04em",
          background: "#0B0910",
          padding: "24px clamp(24px, 4vw, 56px)",
        }}
      >
        <span>© 2026 Iris Machine</span>
        <span>Configure · Comunique · Receba resultados</span>
      </footer>
    </div>
  );
}

import { NavLink, Outlet, useNavigate } from "react-router-dom";
import { useAuth } from "@/features/auth/AuthContext";
import { colors, fonts, radii } from "@/styles/theme";

const navItems = [
  { to: "/painel", icon: "◧", label: "Painel" },
  { to: "/agentes/jimmy/conversa", icon: "◈", label: "Conversa" },
  { to: "/agentes/jimmy/configuracao", icon: "⚙", label: "Configurações" },
];

export function AppLayout() {
  const { user, signOut } = useAuth();
  const navigate = useNavigate();

  async function handleSignOut() {
    await signOut();
    navigate("/", { replace: true });
  }

  return (
    <div
      style={{
        height: "100vh",
        display: "grid",
        gridTemplateColumns: "240px 1fr",
        background: colors.bg,
        color: colors.text,
        overflow: "hidden",
      }}
    >
      <aside
        style={{
          borderRight: `1px solid ${colors.border}`,
          display: "flex",
          flexDirection: "column",
          padding: "20px 14px",
        }}
      >
        <div style={{ padding: "8px 10px 24px" }}>
          <span style={{ fontFamily: fonts.display, fontWeight: 700, fontSize: 15, letterSpacing: "0.2em" }}>
            IRIS
          </span>
        </div>

        <nav style={{ display: "flex", flexDirection: "column", gap: 3 }}>
          {navItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className="iris-nav-item"
              style={({ isActive }) => ({
                display: "flex",
                alignItems: "center",
                gap: 11,
                padding: "10px 12px",
                borderRadius: 9,
                background: isActive ? "rgba(242,73,160,0.12)" : "transparent",
                color: isActive ? colors.accent : colors.textMuted,
                fontSize: 13.5,
                fontWeight: 500,
              })}
            >
              <span style={{ width: 18, textAlign: "center", fontSize: 15 }}>{item.icon}</span>
              {item.label}
            </NavLink>
          ))}
        </nav>

        <div
          style={{
            marginTop: "auto",
            display: "flex",
            alignItems: "center",
            gap: 10,
            padding: "10px 12px",
            borderRadius: radii.md,
            background: "rgba(255,255,255,0.04)",
          }}
        >
          <div
            style={{
              width: 30,
              height: 30,
              borderRadius: "50%",
              flex: "0 0 auto",
              background: `linear-gradient(135deg, ${colors.accent}, ${colors.violet})`,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              fontSize: 12,
              fontWeight: 700,
              color: "#170a14",
            }}
          >
            {(user?.name ?? user?.email ?? "?").charAt(0).toUpperCase()}
          </div>
          <div style={{ display: "flex", flexDirection: "column", minWidth: 0, flex: 1 }}>
            <span
              style={{
                fontSize: 12.5,
                fontWeight: 600,
                overflow: "hidden",
                textOverflow: "ellipsis",
                whiteSpace: "nowrap",
              }}
            >
              {user?.name ?? user?.email}
            </span>
            <button
              type="button"
              onClick={handleSignOut}
              style={{
                background: "transparent",
                border: "none",
                padding: 0,
                textAlign: "left",
                fontSize: 10.5,
                color: colors.textDim,
                cursor: "pointer",
              }}
            >
              Sair
            </button>
          </div>
        </div>
      </aside>

      <main style={{ overflowY: "auto", minWidth: 0 }}>
        <Outlet />
      </main>
    </div>
  );
}

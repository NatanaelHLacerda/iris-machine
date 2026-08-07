import { Link } from "react-router-dom";
import { colors, fonts } from "@/styles/theme";

export interface SidebarLink {
  to: string;
  icon: string;
  label: string;
  active: boolean;
}

export interface AppSidebarProps {
  links: SidebarLink[];
  userName: string;
  userAvatar: string;
  /** Ação exibida sob o nome do usuário (ex.: "Sair"). Sem ação, mostra só o nome. */
  footerAction?: { label: string; onClick: () => void };
  /** Wordmark "IRIS" no topo — só o painel usa hoje. */
  showLogo?: boolean;
}

export function AppSidebar({ links, userName, userAvatar, footerAction, showLogo }: AppSidebarProps) {
  return (
    <aside
      style={{
        borderRight: `1px solid ${colors.border}`,
        display: "flex",
        flexDirection: "column",
        gap: 4,
        padding: showLogo ? "20px 14px" : "18px 12px",
      }}
    >
      {showLogo ? (
        <div style={{ display: "flex", alignItems: "center", gap: 10, padding: "8px 10px 24px" }}>
          <span
            style={{
              fontFamily: fonts.display,
              fontWeight: 700,
              fontSize: 15,
              letterSpacing: "0.2em",
              color: colors.text,
            }}
          >
            IRIS
          </span>
        </div>
      ) : null}

      <nav style={{ display: "flex", flexDirection: "column", gap: 3 }}>
        {links.map((item) => (
          <Link
            key={item.to}
            to={item.to}
            className="iris-nav-item"
            style={{
              display: "flex",
              alignItems: "center",
              gap: 11,
              padding: "10px 12px",
              borderRadius: 9,
              cursor: "pointer",
              color: "inherit",
              background: item.active ? "rgba(242,73,160,0.12)" : "transparent",
            }}
          >
            <span
              style={{
                fontSize: 15,
                width: 18,
                textAlign: "center",
                color: item.active ? colors.accent : colors.textMuted,
              }}
            >
              {item.icon}
            </span>
            <span
              style={{
                fontSize: 13.5,
                fontWeight: 500,
                color: item.active ? colors.accent : colors.textMuted,
              }}
            >
              {item.label}
            </span>
          </Link>
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
          background: footerAction ? colors.bgSubtle : "transparent",
        }}
      >
        <div style={{ width: 30, height: 30, borderRadius: "50%", overflow: "hidden", flex: "0 0 auto" }}>
          <img src={userAvatar} alt="usuário" style={{ width: "100%", height: "100%", objectFit: "cover" }} />
        </div>
        <div style={{ display: "flex", flexDirection: "column", minWidth: 0 }}>
          <span
            style={{
              fontSize: footerAction ? 12.5 : 13,
              fontWeight: footerAction ? 600 : 400,
              color: colors.text,
              overflow: "hidden",
              textOverflow: "ellipsis",
              whiteSpace: "nowrap",
            }}
          >
            {userName}
          </span>
          {footerAction ? (
            <button
              type="button"
              onClick={footerAction.onClick}
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
              {footerAction.label}
            </button>
          ) : null}
        </div>
      </div>
    </aside>
  );
}

import type { CSSProperties, ReactNode } from "react";
import { colors, fonts, radii } from "@/styles/theme";

export const inputStyle: CSSProperties = {
  padding: "11px 14px",
  borderRadius: radii.sm + 1,
  border: `1px solid rgba(255,255,255,0.12)`,
  background: "rgba(255,255,255,0.03)",
  color: colors.text,
  fontSize: 14,
  outline: "none",
  width: "100%",
};

export const cardStyle: CSSProperties = {
  borderRadius: radii.lg,
  border: `1px solid ${colors.border}`,
  background: "rgba(255,255,255,0.02)",
  padding: 20,
};

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: "primary" | "ghost";
  children: ReactNode;
}

export function Button({ variant = "primary", style, children, ...props }: ButtonProps) {
  const base: CSSProperties = {
    padding: "11px 22px",
    borderRadius: radii.md,
    fontWeight: 600,
    fontSize: 14,
    cursor: "pointer",
    border: "none",
  };
  const variants: Record<string, CSSProperties> = {
    primary: { background: colors.accent, color: "#fff" },
    ghost: {
      background: "transparent",
      color: colors.text,
      border: `1px solid ${colors.borderStrong}`,
    },
  };
  return (
    <button className="iris-button-primary" style={{ ...base, ...variants[variant], ...style }} {...props}>
      {children}
    </button>
  );
}

export function Field({
  label,
  hint,
  error,
  required,
  children,
}: {
  label: string;
  hint?: string;
  error?: string;
  required?: boolean;
  children: ReactNode;
}) {
  return (
    <label style={{ display: "flex", flexDirection: "column", gap: 6 }}>
      <span style={{ fontSize: 13, color: colors.textMuted }}>
        {label}
        {required ? " *" : ""}
      </span>
      {children}
      {error ? (
        <span style={{ fontSize: 11.5, color: colors.danger }}>{error}</span>
      ) : hint ? (
        <span style={{ fontSize: 11.5, color: colors.textFaint }}>{hint}</span>
      ) : null}
    </label>
  );
}

export function SectionTitle({ children }: { children: ReactNode }) {
  return (
    <span
      style={{
        fontFamily: fonts.mono,
        fontSize: 11,
        letterSpacing: "0.08em",
        color: colors.accent,
      }}
    >
      {children}
    </span>
  );
}

export function Alert({ tone = "error", children }: { tone?: "error" | "success"; children: ReactNode }) {
  const toneColor = tone === "error" ? colors.danger : colors.success;
  return (
    <div
      role="alert"
      style={{
        padding: "10px 14px",
        borderRadius: radii.sm,
        border: `1px solid ${toneColor}44`,
        background: `${toneColor}14`,
        color: toneColor,
        fontSize: 13,
        lineHeight: 1.45,
      }}
    >
      {children}
    </div>
  );
}

export function StatusDot({ color }: { color: string }) {
  return (
    <span
      style={{
        width: 6,
        height: 6,
        borderRadius: "50%",
        background: color,
        boxShadow: `0 0 6px ${color}`,
        display: "inline-block",
      }}
    />
  );
}

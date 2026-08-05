import { colors, fonts } from "@/styles/theme";

export function FullScreenLoader({ label = "Carregando…" }: { label?: string }) {
  return (
    <div
      style={{
        height: "100vh",
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        gap: 16,
        background: colors.bg,
      }}
    >
      <span
        style={{
          width: 26,
          height: 26,
          borderRadius: "50%",
          border: `2px solid ${colors.border}`,
          borderTopColor: colors.accent,
          animation: "iris-spin 0.8s linear infinite",
        }}
      />
      <span style={{ fontFamily: fonts.mono, fontSize: 11.5, color: colors.textDim, letterSpacing: "0.08em" }}>
        {label}
      </span>
    </div>
  );
}

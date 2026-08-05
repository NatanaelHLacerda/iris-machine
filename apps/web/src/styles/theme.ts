/** Tokens extraídos dos protótipos em prototypes/*.dc.html. */
export const colors = {
  bg: "#0B0910",
  bgElevated: "rgba(255,255,255,0.03)",
  bgSubtle: "rgba(255,255,255,0.04)",
  border: "rgba(255,255,255,0.08)",
  borderStrong: "rgba(255,255,255,0.14)",
  text: "#F4EEF6",
  textMuted: "#b0a2ba",
  textDim: "#7c8894",
  textFaint: "#6f7a85",
  accent: "#F249A0",
  accentHover: "#FF6FB8",
  accentSoft: "#F9A8D0",
  violet: "#7B6CF6",
  success: "#7CF2A6",
  danger: "#F2617A",
} as const;

export const fonts = {
  display: "'Orbitron', sans-serif",
  body: "'Space Grotesk', sans-serif",
  mono: "'IBM Plex Mono', monospace",
} as const;

export const radii = {
  sm: 8,
  md: 10,
  lg: 14,
  xl: 16,
  pill: 999,
} as const;

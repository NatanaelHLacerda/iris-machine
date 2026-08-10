import { useState } from "react";
import { createAgentSchema, type CreateAgentInput } from "@iris/shared";
import { ZodError } from "zod";
import { agentsApi } from "@/features/agents/agents-api";
import { ApiRequestError } from "@/lib/api";
import { colors, fonts, radii } from "@/styles/theme";

const emptyForm: CreateAgentInput = {
  name: "",
  role: "",
  instructions: "",
  model: "",
  vpsAddress: "",
};

const inputStyle = {
  padding: "11px 14px",
  borderRadius: radii.md,
  border: `1px solid ${colors.borderStrong}`,
  background: colors.bgSubtle,
  color: colors.text,
  fontSize: 14,
  width: "100%",
  fontFamily: fonts.body,
} as const;

const labelStyle = {
  fontFamily: fonts.mono,
  fontSize: 11,
  letterSpacing: "0.06em",
  color: colors.textDim,
} as const;

export function NewAgentModal({
  onClose,
  onCreated,
}: {
  onClose: () => void;
  onCreated: () => void;
}) {
  const [form, setForm] = useState<CreateAgentInput>(emptyForm);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const set = (key: keyof CreateAgentInput) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
    setForm((f) => ({ ...f, [key]: e.target.value }));

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setSubmitError(null);
    setFieldErrors({});
    setSaving(true);
    try {
      const parsed = createAgentSchema.parse(form);
      await agentsApi.create(parsed);
      onCreated();
      onClose();
    } catch (err) {
      if (err instanceof ZodError) {
        const next: Record<string, string> = {};
        for (const issue of err.issues) next[String(issue.path[0])] = issue.message;
        setFieldErrors(next);
      } else if (err instanceof ApiRequestError) {
        setSubmitError(err.message);
      } else {
        setSubmitError("Não foi possível criar o agente.");
      }
    } finally {
      setSaving(false);
    }
  }

  return (
    <div
      role="dialog"
      aria-modal
      style={{
        position: "fixed",
        inset: 0,
        background: "rgba(0,0,0,0.55)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        zIndex: 50,
        padding: 20,
      }}
      onClick={onClose}
    >
      <form
        onSubmit={submit}
        onClick={(e) => e.stopPropagation()}
        style={{
          width: "min(440px, 100%)",
          background: colors.bg,
          border: `1px solid ${colors.border}`,
          borderRadius: radii.xl,
          padding: 24,
          display: "flex",
          flexDirection: "column",
          gap: 16,
        }}
      >
        <div>
          <h2 style={{ margin: 0, fontSize: 17, fontWeight: 700, color: colors.text }}>Novo agente</h2>
          <span style={{ fontSize: 12.5, color: colors.textDim }}>
            Cria o registro no painel. Configure VPS e GitHub depois, na tela de configuração.
          </span>
        </div>

        <label style={{ display: "flex", flexDirection: "column", gap: 6 }}>
          <span style={labelStyle}>NOME</span>
          <input style={inputStyle} value={form.name} onChange={set("name")} placeholder="Ex.: Bia" />
          {fieldErrors.name ? <span style={{ fontSize: 12, color: colors.danger }}>{fieldErrors.name}</span> : null}
        </label>

        <label style={{ display: "flex", flexDirection: "column", gap: 6 }}>
          <span style={labelStyle}>PAPEL</span>
          <input
            style={inputStyle}
            value={form.role}
            onChange={set("role")}
            placeholder="Ex.: Integração com Plaud MCP"
          />
          {fieldErrors.role ? <span style={{ fontSize: 12, color: colors.danger }}>{fieldErrors.role}</span> : null}
        </label>

        <label style={{ display: "flex", flexDirection: "column", gap: 6 }}>
          <span style={labelStyle}>MODELO</span>
          <input
            style={inputStyle}
            value={form.model}
            onChange={set("model")}
            placeholder="Ex.: Claude Haiku"
          />
          {fieldErrors.model ? <span style={{ fontSize: 12, color: colors.danger }}>{fieldErrors.model}</span> : null}
        </label>

        <label style={{ display: "flex", flexDirection: "column", gap: 6 }}>
          <span style={labelStyle}>VPS (opcional)</span>
          <input
            style={inputStyle}
            value={form.vpsAddress}
            onChange={set("vpsAddress")}
            placeholder="Ex.: 72-60-126-228.sslip.io"
          />
        </label>

        <label style={{ display: "flex", flexDirection: "column", gap: 6 }}>
          <span style={labelStyle}>INSTRUÇÕES (opcional)</span>
          <textarea
            style={{ ...inputStyle, minHeight: 72, resize: "vertical" }}
            value={form.instructions}
            onChange={set("instructions")}
          />
        </label>

        {submitError ? <span style={{ fontSize: 12.5, color: colors.danger }}>{submitError}</span> : null}

        <div style={{ display: "flex", justifyContent: "flex-end", gap: 10, marginTop: 4 }}>
          <button
            type="button"
            onClick={onClose}
            style={{
              padding: "9px 16px",
              borderRadius: radii.md,
              border: `1px solid ${colors.border}`,
              background: "transparent",
              color: colors.textDim,
              fontSize: 13,
              cursor: "pointer",
            }}
          >
            Cancelar
          </button>
          <button
            type="submit"
            disabled={saving}
            style={{
              padding: "9px 18px",
              borderRadius: radii.md,
              border: "none",
              background: colors.accent,
              color: "#fff",
              fontSize: 13,
              fontWeight: 600,
              cursor: saving ? "default" : "pointer",
              opacity: saving ? 0.7 : 1,
            }}
          >
            {saving ? "Criando…" : "Criar agente"}
          </button>
        </div>
      </form>
    </div>
  );
}

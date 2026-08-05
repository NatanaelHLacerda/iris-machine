import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { agentConfigSchema, type Agent, type AgentConfig } from "@iris/shared";
import { ZodError } from "zod";
import { agentsApi } from "@/features/agents/agents-api";
import { ApiRequestError } from "@/lib/api";
import { Alert, Button, Field, SectionTitle, cardStyle, inputStyle } from "@/components/ui";
import { FullScreenLoader } from "@/components/FullScreenLoader";
import { colors, fonts, radii } from "@/styles/theme";

interface FormState extends Omit<AgentConfig, "githubToken"> {
  githubTokenInput: string;
}

const emptyForm: FormState = {
  vps: "",
  active: false,
  githubTokenInput: "",
  repoPrototype: "",
  repoTarget: "",
  projectName: "",
  stack: "Next.js + TypeScript",
  stackOther: "",
  statePattern: "",
  stateOrg: "",
  routingMech: "",
  routesRegistry: "",
  routesAnim: "",
  folderPattern: "",
  dsPath: "",
  modelsPath: "",
  servicesPath: "",
  fileNaming: "",
  componentNaming: "",
  styleTool: "Tailwind",
  typography: "",
  dsComponents: [],
  httpClient: "fetch",
  errorPattern: "",
  deps: [],
  notes: "",
};

const selectStyle = { ...inputStyle, padding: "10px 12px", fontSize: 13.5 };

function Select({
  value,
  onChange,
  options,
}: {
  value: string;
  onChange: (v: string) => void;
  options: string[];
}) {
  return (
    <select value={value} onChange={(e) => onChange(e.target.value)} style={selectStyle}>
      <option value="">—</option>
      {options.map((o) => (
        <option key={o} value={o}>
          {o}
        </option>
      ))}
      <option value="Outro">Outro</option>
    </select>
  );
}

function Accordion({
  title,
  open,
  onToggle,
  children,
}: {
  title: string;
  open: boolean;
  onToggle: () => void;
  children: ReactNode;
}) {
  return (
    <div style={{ borderTop: `1px solid rgba(255,255,255,0.06)` }}>
      <button
        type="button"
        onClick={onToggle}
        style={{
          width: "100%",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          padding: "13px 2px",
          background: "transparent",
          border: "none",
          color: colors.text,
          fontSize: 13.5,
          fontWeight: 500,
          cursor: "pointer",
        }}
      >
        {title}
        <span style={{ color: colors.textDim, fontSize: 16 }}>{open ? "−" : "+"}</span>
      </button>
      {open ? (
        <div style={{ display: "flex", flexDirection: "column", gap: 12, padding: "4px 2px 18px" }}>{children}</div>
      ) : null}
    </div>
  );
}

export function AgentConfigPage() {
  const { agentId = "jimmy" } = useParams();
  const navigate = useNavigate();

  const [agent, setAgent] = useState<Agent | null>(null);
  const [form, setForm] = useState<FormState>(emptyForm);
  const [hasStoredToken, setHasStoredToken] = useState(false);
  const [replacingToken, setReplacingToken] = useState(false);
  const [depInput, setDepInput] = useState("");
  const [openSections, setOpenSections] = useState<Record<string, boolean>>({ identificacao: true });
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    Promise.all([agentsApi.get(agentId), agentsApi.getConfig(agentId)])
      .then(([{ agent: a }, { config, hasGithubToken }]) => {
        if (cancelled) return;
        setAgent(a);
        setHasStoredToken(hasGithubToken);
        setForm({
          ...emptyForm,
          ...(config ?? {}),
          vps: config?.vps ?? a.vpsAddress ?? "",
          active: config?.active ?? a.active,
          githubTokenInput: "",
        });
      })
      .catch(() => {
        if (!cancelled) setFormError("Não foi possível carregar a configuração.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [agentId]);

  const set = <K extends keyof FormState>(key: K) => (value: FormState[K]) =>
    setForm((prev) => ({ ...prev, [key]: value }));

  const githubComplete = Boolean(
    (hasStoredToken || form.githubTokenInput) && form.repoPrototype && form.repoTarget,
  );
  const canActivate = Boolean(form.vps) && githubComplete;

  const toggleSection = (key: string) =>
    setOpenSections((prev) => ({ ...prev, [key]: !prev[key] }));

  const textField = useMemo(
    () =>
      function TextField(key: keyof FormState, placeholder?: string) {
        return (
          <input
            type="text"
            value={String(form[key] ?? "")}
            placeholder={placeholder}
            onChange={(e) => set(key)(e.target.value as FormState[typeof key])}
            style={selectStyle}
          />
        );
      },
    [form],
  );

  function addDep() {
    const value = depInput.trim();
    if (!value) return;
    setForm((prev) => ({ ...prev, deps: [...prev.deps, value] }));
    setDepInput("");
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setFieldErrors({});
    setFormError(null);
    setSaved(false);
    setSaving(true);

    try {
      const { githubTokenInput, ...rest } = form;
      const payload = agentConfigSchema.parse({
        ...rest,
        // Token vazio = manter o já salvo no servidor.
        githubToken: githubTokenInput || undefined,
      });
      const result = await agentsApi.saveConfig(agentId, payload);
      setHasStoredToken(result.hasGithubToken);
      setReplacingToken(false);
      setForm((prev) => ({ ...prev, githubTokenInput: "" }));
      setSaved(true);
    } catch (err) {
      if (err instanceof ZodError) {
        const next: Record<string, string> = {};
        for (const issue of err.issues) {
          const key = issue.path.join(".");
          if (key && !next[key]) next[key] = issue.message;
        }
        setFieldErrors(next);
        setFormError("Revise os campos destacados.");
      } else if (err instanceof ApiRequestError) {
        setFormError(err.message);
      } else {
        setFormError("Não foi possível salvar.");
      }
    } finally {
      setSaving(false);
    }
  }

  if (loading) return <FullScreenLoader label="Carregando configuração…" />;

  const showTokenInput = !hasStoredToken || replacingToken;

  return (
    <form
      onSubmit={handleSubmit}
      style={{
        padding: "26px clamp(20px, 3vw, 40px) 40px",
        display: "flex",
        flexDirection: "column",
        gap: 18,
        maxWidth: 860,
      }}
    >
      <header>
        <h1 style={{ margin: 0, fontSize: 22, fontWeight: 700 }}>Configurar {agent?.name}</h1>
        <span style={{ fontFamily: fonts.mono, fontSize: 11.5, color: colors.textDim }}>
          Configuração persistente do agente
        </span>
      </header>

      {formError ? <Alert>{formError}</Alert> : null}
      {saved ? <Alert tone="success">Configuração salva.</Alert> : null}

      {/* Seção 1 — Identidade (somente exibição, vem do SOUL.md) */}
      <div style={{ ...cardStyle, background: "rgba(255,255,255,0.04)", display: "flex", flexDirection: "column", gap: 14 }}>
        <SectionTitle>🔒 IDENTIDADE</SectionTitle>
        {[
          { label: "Nome do agente", value: agent?.name },
          { label: "Função / especialidade", value: agent?.role },
          { label: "Instruções de comportamento", value: agent?.instructions },
        ].map((item) => (
          <div key={item.label}>
            <span style={{ display: "block", fontSize: 11.5, color: colors.textFaint, marginBottom: 3 }}>
              {item.label}
            </span>
            <span style={{ fontSize: 13.5, lineHeight: 1.55, color: "#a89db2" }}>{item.value}</span>
          </div>
        ))}
      </div>

      {/* Seção 2 — Conexão */}
      <div style={{ ...cardStyle, display: "flex", flexDirection: "column", gap: 16 }}>
        <SectionTitle>CONEXÃO</SectionTitle>
        <div>
          <span style={{ display: "block", fontSize: 11.5, color: colors.textFaint, marginBottom: 3 }}>Modelo</span>
          <span style={{ fontSize: 13.5, color: "#a89db2" }}>{agent?.model}</span>
        </div>
        <Field label="Endereço VPS" required error={fieldErrors.vps}>
          <input
            type="text"
            value={form.vps}
            onChange={(e) => set("vps")(e.target.value)}
            placeholder="vps.hostinger.com:8443"
            style={inputStyle}
          />
        </Field>
        <label style={{ display: "flex", alignItems: "center", gap: 10, cursor: canActivate ? "pointer" : "not-allowed" }}>
          <input
            type="checkbox"
            checked={form.active}
            disabled={!canActivate && !form.active}
            onChange={(e) => set("active")(e.target.checked)}
          />
          <span style={{ fontSize: 13, color: colors.textMuted }}>
            Ativo
            {!canActivate && !form.active ? (
              <span style={{ color: colors.textFaint, fontSize: 11.5 }}> — preencha VPS e GitHub para ativar</span>
            ) : null}
          </span>
        </label>
      </div>

      {/* Seção 3 — GitHub */}
      <div style={{ ...cardStyle, display: "flex", flexDirection: "column", gap: 16 }}>
        <SectionTitle>GITHUB</SectionTitle>

        <Field
          label="Token de acesso (PAT)"
          required
          hint="Escopo repo (+ workflow se usar CI/CD). Nunca é reexibido após salvo."
        >
          {showTokenInput ? (
            <input
              type="password"
              value={form.githubTokenInput}
              onChange={(e) => set("githubTokenInput")(e.target.value)}
              placeholder="ghp_••••••••••••"
              autoComplete="off"
              style={inputStyle}
            />
          ) : (
            <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
              <span
                style={{
                  ...inputStyle,
                  color: colors.textDim,
                  fontFamily: fonts.mono,
                  flex: 1,
                }}
              >
                ••••••••••••••••
              </span>
              <Button type="button" variant="ghost" onClick={() => setReplacingToken(true)}>
                Substituir
              </Button>
            </div>
          )}
        </Field>

        <Field label="Repositório protótipo" required hint="Referência, somente leitura" error={fieldErrors.repoPrototype}>
          <input
            type="url"
            value={form.repoPrototype}
            onChange={(e) => set("repoPrototype")(e.target.value)}
            placeholder="https://github.com/org/prototipo"
            style={inputStyle}
          />
        </Field>

        <Field
          label="Repositório destino"
          required
          hint={`Onde o ${agent?.name ?? "agente"} escreve — precisa de permissão de escrita. Branch gerada automaticamente por tarefa.`}
          error={fieldErrors.repoTarget}
        >
          <input
            type="url"
            value={form.repoTarget}
            onChange={(e) => set("repoTarget")(e.target.value)}
            placeholder="https://github.com/org/destino"
            style={inputStyle}
          />
        </Field>
      </div>

      {/* Seção 4 — Convenções do projeto destino */}
      <div style={{ ...cardStyle, display: "flex", flexDirection: "column", gap: 6 }}>
        <SectionTitle>CONVENÇÕES DO PROJETO DESTINO</SectionTitle>

        <Accordion title="4.1 Identificação" open={!!openSections.identificacao} onToggle={() => toggleSection("identificacao")}>
          <Field label="Nome do projeto" required error={fieldErrors.projectName}>
            {textField("projectName")}
          </Field>
          <Field label="Stack" required error={fieldErrors.stack}>
            <Select
              value={form.stack}
              onChange={set("stack")}
              options={["Next.js + TypeScript", "React + Vite", "React Native + Expo"]}
            />
          </Field>
          {form.stack === "Outro" ? (
            <Field label="Especifique a stack">{textField("stackOther")}</Field>
          ) : null}
        </Accordion>

        <Accordion title="4.2 Gerenciamento de estado" open={!!openSections.estado} onToggle={() => toggleSection("estado")}>
          <Field label="Padrão adotado">
            <Select
              value={form.statePattern ?? ""}
              onChange={set("statePattern")}
              options={["Context API", "Redux", "Zustand", "React Query"]}
            />
          </Field>
          <Field label="Convenção de organização">{textField("stateOrg")}</Field>
        </Accordion>

        <Accordion title="4.3 Navegação / rotas" open={!!openSections.navegacao} onToggle={() => toggleSection("navegacao")}>
          <Field label="Mecanismo">
            <Select
              value={form.routingMech ?? ""}
              onChange={set("routingMech")}
              options={["App Router", "React Router", "React Navigation"]}
            />
          </Field>
          <Field label="Registro de rotas">{textField("routesRegistry")}</Field>
          <Field label="Transições / animações customizadas">{textField("routesAnim")}</Field>
        </Accordion>

        <Accordion title="4.4 Estrutura de pastas" open={!!openSections.pastas} onToggle={() => toggleSection("pastas")}>
          <Field label="Padrão">
            <Select
              value={form.folderPattern ?? ""}
              onChange={set("folderPattern")}
              options={["por feature", "por tipo", "colocation"]}
            />
          </Field>
          <Field label="Design system central (caminho)">{textField("dsPath")}</Field>
          <Field label="Modelos de dados / tipos (caminho)">{textField("modelsPath")}</Field>
          <Field label="Serviços / API (caminho + cliente)">{textField("servicesPath")}</Field>
        </Accordion>

        <Accordion title="4.5 Estilo e nomenclatura" open={!!openSections.estilo} onToggle={() => toggleSection("estilo")}>
          <Field label="Nome de arquivo / pasta">{textField("fileNaming")}</Field>
          <Field label="Nome de componente">{textField("componentNaming")}</Field>
          <Field label="Estilos">
            <Select
              value={form.styleTool ?? ""}
              onChange={set("styleTool")}
              options={["Tailwind", "CSS Modules", "styled-components"]}
            />
          </Field>
          <Field label="Tipografia (fonte + onde configurada)">{textField("typography")}</Field>
        </Accordion>

        <Accordion
          title="4.6 Componentes de design system"
          open={!!openSections.componentes}
          onToggle={() => toggleSection("componentes")}
        >
          {form.dsComponents.map((row, index) => (
            <div key={index} style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr auto", gap: 8 }}>
              {(["name", "loc", "usage"] as const).map((field) => (
                <input
                  key={field}
                  type="text"
                  value={row[field]}
                  placeholder={{ name: "Componente", loc: "Localização", usage: "Uso típico" }[field]}
                  onChange={(e) =>
                    setForm((prev) => ({
                      ...prev,
                      dsComponents: prev.dsComponents.map((r, i) =>
                        i === index ? { ...r, [field]: e.target.value } : r,
                      ),
                    }))
                  }
                  style={selectStyle}
                />
              ))}
              <button
                type="button"
                onClick={() =>
                  setForm((prev) => ({
                    ...prev,
                    dsComponents: prev.dsComponents.filter((_, i) => i !== index),
                  }))
                }
                style={{ background: "transparent", border: "none", color: colors.textDim, cursor: "pointer", fontSize: 16 }}
              >
                ×
              </button>
            </div>
          ))}
          <Button
            type="button"
            variant="ghost"
            style={{ alignSelf: "flex-start", padding: "7px 12px", fontSize: 12.5 }}
            onClick={() =>
              setForm((prev) => ({
                ...prev,
                dsComponents: [...prev.dsComponents, { name: "", loc: "", usage: "" }],
              }))
            }
          >
            + adicionar componente
          </Button>
        </Accordion>

        <Accordion title="4.7 Rede e dados" open={!!openSections.rede} onToggle={() => toggleSection("rede")}>
          <Field label="Cliente HTTP">
            <Select
              value={form.httpClient ?? ""}
              onChange={set("httpClient")}
              options={["fetch", "axios", "react-query", "swr"]}
            />
          </Field>
          <Field label="Padrão de tratamento de erro de API">{textField("errorPattern")}</Field>
        </Accordion>

        <Accordion
          title="4.8 Dependências já presentes"
          open={!!openSections.dependencias}
          onToggle={() => toggleSection("dependencias")}
        >
          <div style={{ display: "flex", flexWrap: "wrap", gap: 7 }}>
            {form.deps.map((dep, index) => (
              <span
                key={`${dep}-${index}`}
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: 6,
                  fontFamily: fonts.mono,
                  fontSize: 11.5,
                  padding: "5px 10px",
                  borderRadius: radii.pill,
                  border: `1px solid rgba(242,73,160,0.2)`,
                  color: "#b5a3c0",
                }}
              >
                {dep}
                <span
                  onClick={() =>
                    setForm((prev) => ({ ...prev, deps: prev.deps.filter((_, i) => i !== index) }))
                  }
                  style={{ cursor: "pointer", color: colors.textDim }}
                >
                  ×
                </span>
              </span>
            ))}
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <input
              type="text"
              value={depInput}
              placeholder="ex: zod"
              onChange={(e) => setDepInput(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  addDep();
                }
              }}
              style={selectStyle}
            />
            <Button type="button" variant="ghost" onClick={addDep} style={{ whiteSpace: "nowrap" }}>
              Adicionar
            </Button>
          </div>
        </Accordion>

        <Accordion
          title="4.9 Observações adicionais"
          open={!!openSections.observacoes}
          onToggle={() => toggleSection("observacoes")}
        >
          <textarea
            rows={3}
            value={form.notes ?? ""}
            onChange={(e) => set("notes")(e.target.value)}
            style={{ ...inputStyle, resize: "vertical" }}
          />
        </Accordion>
      </div>

      <div style={{ display: "flex", gap: 12, justifyContent: "flex-end" }}>
        <Button type="button" variant="ghost" onClick={() => navigate("/painel")}>
          Cancelar
        </Button>
        <Button type="submit" disabled={saving}>
          {saving ? "Salvando…" : "Salvar alterações"}
        </Button>
      </div>
    </form>
  );
}

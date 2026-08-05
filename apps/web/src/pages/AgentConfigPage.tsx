import { useEffect, useState, type FormEvent, type ReactNode } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { agentConfigSchema, type Agent, type AgentConfig } from "@iris/shared";
import { ZodError } from "zod";
import { agentsApi } from "@/features/agents/agents-api";
import { ApiRequestError } from "@/lib/api";
import { FullScreenLoader } from "@/components/FullScreenLoader";

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

const cardStyle = {
  borderRadius: 14,
  border: "1px solid rgba(255,255,255,0.08)",
  background: "rgba(255,255,255,0.02)",
  padding: 20,
  display: "flex",
  flexDirection: "column",
  gap: 16,
} as const;

const sectionLabelStyle = {
  fontFamily: "'IBM Plex Mono', monospace",
  fontSize: 11,
  letterSpacing: "0.08em",
  color: "#F249A0",
} as const;

const inputStyle = {
  padding: "11px 14px",
  borderRadius: 9,
  border: "1px solid rgba(255,255,255,0.12)",
  background: "rgba(255,255,255,0.03)",
  color: "#F4EEF6",
  fontSize: 14,
  width: "100%",
} as const;

const smallInputStyle = { ...inputStyle, padding: "10px 12px", fontSize: 13.5 } as const;

function Label({
  text,
  hint,
  error,
  required,
  children,
}: {
  text: string;
  hint?: ReactNode;
  error?: string;
  required?: boolean;
  children: ReactNode;
}) {
  return (
    <label style={{ display: "flex", flexDirection: "column", gap: 6 }}>
      <span style={{ fontSize: 13, color: "#b0a2ba" }}>
        {text}
        {required ? " *" : ""}
      </span>
      {children}
      {error ? (
        <span style={{ fontSize: 11.5, color: "#F2617A" }}>{error}</span>
      ) : hint ? (
        <span style={{ fontSize: 11.5, color: "#6f7a85" }}>{hint}</span>
      ) : null}
    </label>
  );
}

function Select({ value, onChange, options }: { value: string; onChange: (v: string) => void; options: string[] }) {
  return (
    <select value={value} onChange={(e) => onChange(e.target.value)} style={smallInputStyle}>
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

function Toggle({ checked, disabled, onChange }: { checked: boolean; disabled: boolean; onChange: () => void }) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={checked}
      disabled={disabled}
      onClick={onChange}
      style={{
        width: 42,
        height: 24,
        borderRadius: 999,
        border: "none",
        padding: 3,
        background: checked ? "#F249A0" : "rgba(255,255,255,0.12)",
        cursor: disabled ? "not-allowed" : "pointer",
        opacity: disabled ? 0.5 : 1,
        display: "flex",
        justifyContent: checked ? "flex-end" : "flex-start",
        transition: "background 0.2s ease",
      }}
    >
      <span style={{ width: 18, height: 18, borderRadius: "50%", background: "#fff", display: "block" }} />
    </button>
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
    <div style={{ borderTop: "1px solid rgba(255,255,255,0.06)" }}>
      <button
        type="button"
        className="acc-head"
        onClick={onToggle}
        style={{
          width: "100%",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          padding: "13px 2px",
          background: "transparent",
          border: "none",
          color: "#F4EEF6",
          fontSize: 13.5,
          fontWeight: 500,
          cursor: "pointer",
        }}
      >
        {title}
        <span style={{ color: "#7c8894", fontSize: 16 }}>{open ? "−" : "+"}</span>
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
  const [openSections, setOpenSections] = useState<Record<string, boolean>>({});
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

  const set =
    <K extends keyof FormState>(key: K) =>
    (value: FormState[K]) =>
      setForm((prev) => ({ ...prev, [key]: value }));

  const text = (key: keyof FormState, placeholder?: string) => (
    <input
      type="text"
      value={String(form[key] ?? "")}
      placeholder={placeholder}
      onChange={(e) => set(key)(e.target.value as FormState[typeof key])}
      style={smallInputStyle}
    />
  );

  const githubComplete = Boolean((hasStoredToken || form.githubTokenInput) && form.repoPrototype && form.repoTarget);
  const canActivate = Boolean(form.vps) && githubComplete;

  const toggleSection = (key: string) => setOpenSections((prev) => ({ ...prev, [key]: !prev[key] }));

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
    <div
      style={{
        minHeight: "100vh",
        width: "100%",
        background: "#0B0910",
        color: "#F4EEF6",
        fontFamily: "'Space Grotesk', sans-serif",
        padding: "26px clamp(20px, 4vw, 56px) 60px",
      }}
    >
      <div style={{ maxWidth: 760, margin: "0 auto" }}>
        <Link
          to="/painel"
          style={{
            display: "inline-flex",
            alignItems: "center",
            gap: 6,
            fontFamily: "'IBM Plex Mono', monospace",
            fontSize: 12,
            color: "#7c8894",
            marginBottom: 24,
          }}
        >
          ← Voltar ao painel
        </Link>

        <div style={{ display: "flex", alignItems: "center", gap: 16, marginBottom: 32 }}>
          <div
            style={{
              width: 56,
              height: 56,
              borderRadius: 14,
              overflow: "hidden",
              flex: "0 0 auto",
              boxShadow: "0 0 0 1px rgba(242,73,160,0.3)",
            }}
          >
            <img
              src={agent?.avatarUrl ?? "/uploads/761a440f4d38e77c845b67badf122797.jpg"}
              alt={agent?.name}
              style={{ width: "100%", height: "100%", objectFit: "cover" }}
            />
          </div>
          <div>
            <h1 style={{ margin: 0, fontSize: 22, fontWeight: 700 }}>Configurar {agent?.name}</h1>
            <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: "#7c8894" }}>
              {agent?.role}
            </span>
          </div>
        </div>

        <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: 22 }}>
          {formError ? (
            <div
              style={{
                padding: "10px 14px",
                borderRadius: 9,
                border: "1px solid rgba(242,97,122,0.27)",
                background: "rgba(242,97,122,0.08)",
                color: "#F2617A",
                fontSize: 13,
              }}
            >
              {formError}
            </div>
          ) : null}
          {saved ? (
            <div
              style={{
                padding: "10px 14px",
                borderRadius: 9,
                border: "1px solid rgba(124,242,166,0.27)",
                background: "rgba(124,242,166,0.08)",
                color: "#7CF2A6",
                fontSize: 13,
              }}
            >
              Configuração salva.
            </div>
          ) : null}

          {/* Seção 1 — Identidade (somente exibição) */}
          <div
            style={{
              borderRadius: 14,
              border: "1px solid rgba(255,255,255,0.06)",
              background: "rgba(255,255,255,0.015)",
              padding: 20,
              display: "flex",
              flexDirection: "column",
              gap: 14,
            }}
          >
            <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
              <span style={{ fontSize: 13, color: "#6f7a85" }}>🔒</span>
              <span
                style={{
                  fontFamily: "'IBM Plex Mono', monospace",
                  fontSize: 11,
                  letterSpacing: "0.08em",
                  color: "#6f7a85",
                }}
              >
                IDENTIDADE · definida em SOUL.md
              </span>
            </div>
            <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
              {[
                { label: "Nome do agente", value: agent?.name },
                { label: "Função / especialidade", value: agent?.role },
                { label: "Instruções de comportamento", value: agent?.instructions },
              ].map((item) => (
                <div key={item.label}>
                  <span style={{ display: "block", fontSize: 11.5, color: "#6f7a85", marginBottom: 3 }}>
                    {item.label}
                  </span>
                  <span style={{ fontSize: 14.5, color: "#C7BECE", lineHeight: 1.55 }}>{item.value}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Seção 2 — Conexão */}
          <div style={cardStyle}>
            <span style={sectionLabelStyle}>CONEXÃO</span>

            <div>
              <span style={{ display: "block", fontSize: 11.5, color: "#6f7a85", marginBottom: 3 }}>Modelo</span>
              <span style={{ fontSize: 14.5, color: "#C7BECE" }}>{agent?.model}</span>
            </div>

            <Label text="Endereço VPS" required error={fieldErrors.vps}>
              <input
                type="text"
                value={form.vps}
                onChange={(e) => set("vps")(e.target.value)}
                placeholder="vps.hostinger.com:8443"
                style={inputStyle}
              />
            </Label>

            <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 12 }}>
              <div style={{ display: "flex", flexDirection: "column", gap: 3 }}>
                <span style={{ fontSize: 13, color: "#b0a2ba" }}>Ativo</span>
                {!canActivate && !form.active ? (
                  <span style={{ fontSize: 11.5, color: "#6f7a85" }}>
                    Preencha VPS e a seção GitHub para poder ativar
                  </span>
                ) : null}
              </div>
              <Toggle
                checked={form.active}
                disabled={!canActivate && !form.active}
                onChange={() => set("active")(!form.active)}
              />
            </div>
          </div>

          {/* Seção 3 — GitHub */}
          <div style={cardStyle}>
            <span style={sectionLabelStyle}>GITHUB</span>

            <Label
              text="Token de acesso (PAT)"
              required
              hint={
                <>
                  Escopo <code style={{ color: "#b0a2ba" }}>repo</code> (+{" "}
                  <code style={{ color: "#b0a2ba" }}>workflow</code> se usar CI/CD)
                </>
              }
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
                      padding: "11px 14px",
                      borderRadius: 9,
                      border: "1px solid rgba(255,255,255,0.12)",
                      background: "rgba(255,255,255,0.03)",
                      color: "#7c8894",
                      fontFamily: "'IBM Plex Mono', monospace",
                      fontSize: 14,
                      flex: 1,
                    }}
                  >
                    ••••••••••••••••
                  </span>
                  <button
                    type="button"
                    onClick={() => setReplacingToken(true)}
                    style={{
                      padding: "10px 14px",
                      borderRadius: 9,
                      border: "1px solid rgba(255,255,255,0.14)",
                      background: "transparent",
                      color: "#F4EEF6",
                      fontSize: 13,
                      cursor: "pointer",
                    }}
                  >
                    Substituir
                  </button>
                </div>
              )}
            </Label>

            <Label
              text="Repositório protótipo"
              required
              hint="Referência, somente leitura"
              error={fieldErrors.repoPrototype}
            >
              <input
                type="url"
                value={form.repoPrototype}
                onChange={(e) => set("repoPrototype")(e.target.value)}
                placeholder="https://github.com/org/prototipo"
                style={inputStyle}
              />
            </Label>

            <Label
              text="Repositório destino"
              required
              hint={`Onde o ${agent?.name ?? "agente"} escreve — precisa de permissão de escrita. Branch gerada automaticamente por tarefa (${agent?.name?.toLowerCase() ?? "agente"}/<feature>).`}
              error={fieldErrors.repoTarget}
            >
              <input
                type="url"
                value={form.repoTarget}
                onChange={(e) => set("repoTarget")(e.target.value)}
                placeholder="https://github.com/org/destino"
                style={inputStyle}
              />
            </Label>
          </div>

          {/* Seção 4 — Convenções do projeto destino */}
          <div style={{ ...cardStyle, gap: 6 }}>
            <span style={{ ...sectionLabelStyle, marginBottom: 6 }}>CONVENÇÕES DO PROJETO DESTINO</span>

            <Accordion
              title="4.1 Identificação"
              open={!!openSections.identificacao}
              onToggle={() => toggleSection("identificacao")}
            >
              <Label text="Nome do projeto" required error={fieldErrors.projectName}>
                {text("projectName")}
              </Label>
              <Label text="Stack" required error={fieldErrors.stack}>
                <Select
                  value={form.stack}
                  onChange={set("stack")}
                  options={["Next.js + TypeScript", "React + Vite", "React Native + Expo"]}
                />
              </Label>
              {form.stack === "Outro" ? (
                <Label text="Especifique a stack">{text("stackOther", "Especifique a stack")}</Label>
              ) : null}
            </Accordion>

            <Accordion
              title="4.2 Gerenciamento de estado"
              open={!!openSections.estado}
              onToggle={() => toggleSection("estado")}
            >
              <Label text="Padrão adotado">
                <Select
                  value={form.statePattern ?? ""}
                  onChange={set("statePattern")}
                  options={["Context API", "Redux", "Zustand", "React Query"]}
                />
              </Label>
              <Label text="Convenção de organização">{text("stateOrg")}</Label>
            </Accordion>

            <Accordion
              title="4.3 Navegação/rotas"
              open={!!openSections.navegacao}
              onToggle={() => toggleSection("navegacao")}
            >
              <Label text="Mecanismo">
                <Select
                  value={form.routingMech ?? ""}
                  onChange={set("routingMech")}
                  options={["App Router", "React Router", "React Navigation"]}
                />
              </Label>
              <Label text="Registro de rotas">{text("routesRegistry")}</Label>
              <Label text="Transições/animações customizadas">{text("routesAnim")}</Label>
            </Accordion>

            <Accordion
              title="4.4 Estrutura de pastas"
              open={!!openSections.pastas}
              onToggle={() => toggleSection("pastas")}
            >
              <Label text="Padrão">
                <Select
                  value={form.folderPattern ?? ""}
                  onChange={set("folderPattern")}
                  options={["por feature", "por tipo", "colocation"]}
                />
              </Label>
              <Label text="Design system central (caminho)">{text("dsPath")}</Label>
              <Label text="Modelos de dados/tipos (caminho)">{text("modelsPath")}</Label>
              <Label text="Serviços/API (caminho + cliente)">{text("servicesPath")}</Label>
            </Accordion>

            <Accordion
              title="4.5 Estilo e nomenclatura"
              open={!!openSections.estilo}
              onToggle={() => toggleSection("estilo")}
            >
              <Label text="Nome de arquivo/pasta">{text("fileNaming")}</Label>
              <Label text="Nome de componente">{text("componentNaming")}</Label>
              <Label text="Estilos">
                <Select
                  value={form.styleTool ?? ""}
                  onChange={set("styleTool")}
                  options={["Tailwind", "CSS Modules", "styled-components"]}
                />
              </Label>
              <Label text="Tipografia (fonte + onde configurada)">{text("typography")}</Label>
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
                      style={smallInputStyle}
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
                    style={{
                      background: "transparent",
                      border: "none",
                      color: "#7c8894",
                      cursor: "pointer",
                      fontSize: 16,
                    }}
                  >
                    ×
                  </button>
                </div>
              ))}
              <button
                type="button"
                onClick={() =>
                  setForm((prev) => ({
                    ...prev,
                    dsComponents: [...prev.dsComponents, { name: "", loc: "", usage: "" }],
                  }))
                }
                style={{
                  alignSelf: "flex-start",
                  padding: "7px 12px",
                  borderRadius: 7,
                  border: "1px solid rgba(255,255,255,0.14)",
                  background: "transparent",
                  color: "#F4EEF6",
                  fontSize: 12.5,
                  cursor: "pointer",
                }}
              >
                + adicionar componente
              </button>
            </Accordion>

            <Accordion title="4.7 Rede e dados" open={!!openSections.rede} onToggle={() => toggleSection("rede")}>
              <Label text="Cliente HTTP">
                <Select
                  value={form.httpClient ?? ""}
                  onChange={set("httpClient")}
                  options={["fetch", "axios", "react-query", "swr"]}
                />
              </Label>
              <Label text="Padrão de tratamento de erro de API">{text("errorPattern")}</Label>
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
                      fontFamily: "'IBM Plex Mono', monospace",
                      fontSize: 11.5,
                      padding: "5px 10px",
                      borderRadius: 999,
                      border: "1px solid rgba(242,73,160,0.2)",
                      color: "#b5a3c0",
                    }}
                  >
                    {dep}
                    <span
                      className="chip-x"
                      onClick={() => setForm((prev) => ({ ...prev, deps: prev.deps.filter((_, i) => i !== index) }))}
                      style={{ cursor: "pointer", color: "#7c8894" }}
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
                  style={smallInputStyle}
                />
                <button
                  type="button"
                  onClick={addDep}
                  style={{
                    padding: "9px 14px",
                    borderRadius: 8,
                    border: "none",
                    background: "rgba(242,73,160,0.15)",
                    color: "#F9A8D0",
                    fontSize: 12.5,
                    cursor: "pointer",
                    whiteSpace: "nowrap",
                  }}
                >
                  Adicionar
                </button>
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
                style={{ ...inputStyle, fontSize: 13.5, resize: "vertical" }}
              />
            </Accordion>
          </div>

          <div style={{ display: "flex", gap: 12, justifyContent: "flex-end" }}>
            <button
              type="button"
              onClick={() => navigate("/painel")}
              style={{
                padding: "12px 22px",
                borderRadius: 10,
                border: "1px solid rgba(255,255,255,0.14)",
                background: "transparent",
                color: "#F4EEF6",
                fontWeight: 600,
                fontSize: 14,
                cursor: "pointer",
              }}
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={saving}
              className="iris-button-primary"
              style={{
                padding: "12px 24px",
                borderRadius: 10,
                border: "none",
                background: "#F249A0",
                color: "#fff",
                fontFamily: "'Space Grotesk', sans-serif",
                fontWeight: 600,
                fontSize: 14,
                cursor: "pointer",
              }}
            >
              {saving ? "Salvando…" : "Salvar alterações"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

import { useState, type FormEvent } from "react";
import { signInSchema, signUpSchema } from "@iris/shared";
import { ZodError } from "zod";
import { ApiRequestError } from "@/lib/api";
import { Alert, Button, Field, inputStyle } from "@/components/ui";
import { colors } from "@/styles/theme";
import { useAuth } from "./AuthContext";

type Mode = "signin" | "signup";

export function AuthForm({ onSuccess }: { onSuccess: () => void }) {
  const { signIn, signUp } = useAuth();
  const [mode, setMode] = useState<Mode>("signin");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  function switchMode(next: Mode) {
    setMode(next);
    setFieldErrors({});
    setFormError(null);
    setNotice(null);
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setFieldErrors({});
    setFormError(null);
    setNotice(null);
    setSubmitting(true);

    try {
      if (mode === "signin") {
        await signIn(signInSchema.parse({ email, password }));
        onSuccess();
      } else {
        const input = signUpSchema.parse({ email, password, name: name || undefined });
        const { emailConfirmationRequired } = await signUp(input);
        if (emailConfirmationRequired) {
          setNotice("Conta criada. Confirme o e-mail enviado para concluir o acesso.");
        } else {
          onSuccess();
        }
      }
    } catch (err) {
      if (err instanceof ZodError) {
        const next: Record<string, string> = {};
        for (const issue of err.issues) {
          const key = issue.path.join(".");
          if (key && !next[key]) next[key] = issue.message;
        }
        setFieldErrors(next);
      } else if (err instanceof ApiRequestError) {
        setFormError(err.message);
      } else {
        setFormError("Não foi possível concluir. Tente novamente.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: 12, width: "100%" }}>
      {formError ? <Alert>{formError}</Alert> : null}
      {notice ? <Alert tone="success">{notice}</Alert> : null}

      {mode === "signup" ? (
        <Field label="Nome" error={fieldErrors.name}>
          <input
            type="text"
            value={name}
            onChange={(e) => setName(e.target.value)}
            autoComplete="name"
            style={inputStyle}
          />
        </Field>
      ) : null}

      <Field label="E-mail" required error={fieldErrors.email}>
        <input
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          autoComplete="email"
          style={inputStyle}
        />
      </Field>

      <Field
        label="Senha"
        required
        error={fieldErrors.password}
        hint={mode === "signup" ? "Mínimo de 8 caracteres" : undefined}
      >
        <input
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          autoComplete={mode === "signup" ? "new-password" : "current-password"}
          style={inputStyle}
        />
      </Field>

      <Button type="submit" disabled={submitting} style={{ marginTop: 4 }}>
        {submitting ? "Aguarde…" : mode === "signin" ? "Entrar" : "Criar conta"}
      </Button>

      <button
        type="button"
        onClick={() => switchMode(mode === "signin" ? "signup" : "signin")}
        style={{
          background: "transparent",
          border: "none",
          color: colors.textMuted,
          fontSize: 12.5,
          cursor: "pointer",
          padding: 0,
        }}
      >
        {mode === "signin" ? "Não tem conta? Cadastre-se" : "Já tem conta? Entrar"}
      </button>
    </form>
  );
}

import { useEffect, useRef, useState, type FormEvent, type KeyboardEvent } from "react";
import { useParams } from "react-router-dom";
import type { Agent, ChatMessage } from "@iris/shared";
import { agentsApi } from "@/features/agents/agents-api";
import { Alert, StatusDot } from "@/components/ui";
import { FullScreenLoader } from "@/components/FullScreenLoader";
import { colors, fonts } from "@/styles/theme";

export function ChatPage() {
  const { agentId = "jimmy" } = useParams();
  const [agent, setAgent] = useState<Agent | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [input, setInput] = useState("");
  const [sending, setSending] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const scrollRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    Promise.all([agentsApi.get(agentId), agentsApi.messages(agentId)])
      .then(([{ agent: a }, { messages: m }]) => {
        if (cancelled) return;
        setAgent(a);
        setMessages(m);
      })
      .catch(() => {
        if (!cancelled) setError("Não foi possível carregar a conversa.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [agentId]);

  useEffect(() => {
    const node = scrollRef.current;
    if (node) node.scrollTop = node.scrollHeight;
  }, [messages, sending]);

  async function send() {
    const content = input.trim();
    if (!content || sending) return;

    setInput("");
    setSending(true);
    setError(null);
    try {
      const { messages: created } = await agentsApi.sendMessage(agentId, content);
      setMessages((prev) => [...prev, ...created]);
    } catch {
      setError("Falha ao enviar a mensagem.");
      setInput(content);
    } finally {
      setSending(false);
    }
  }

  function handleKeyDown(event: KeyboardEvent<HTMLTextAreaElement>) {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      void send();
    }
  }

  function handleSubmit(event: FormEvent) {
    event.preventDefault();
    void send();
  }

  if (loading) return <FullScreenLoader label="Abrindo conversa…" />;

  const greeting: ChatMessage | null = agent
    ? {
        id: "__greeting",
        agentId,
        author: "agent",
        content: `Olá! Eu sou o ${agent.name}, especialista em ${agent.role.toLowerCase()}. Me envie um trecho ou descreva o que precisa.`,
        createdAt: agent.lastRunAt ?? new Date().toISOString(),
      }
    : null;

  const allMessages = greeting ? [greeting, ...messages] : messages;

  return (
    <div
      style={{
        height: "100vh",
        display: "flex",
        flexDirection: "column",
        background: "radial-gradient(120% 100% at 85% 0%, #1a1122 0%, #0B0910 55%)",
      }}
    >
      <header
        style={{
          flex: "0 0 auto",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          padding: "16px clamp(20px, 3vw, 40px)",
          borderBottom: `1px solid ${colors.border}`,
        }}
      >
        <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
          <div style={{ width: 34, height: 34, borderRadius: 10, overflow: "hidden" }}>
            {agent?.avatarUrl ? (
              <img src={agent.avatarUrl} alt={agent.name} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
            ) : null}
          </div>
          <div style={{ display: "flex", flexDirection: "column", gap: 1 }}>
            <span style={{ fontWeight: 600, fontSize: 15 }}>{agent?.name}</span>
            <span
              style={{
                display: "flex",
                alignItems: "center",
                gap: 5,
                fontFamily: fonts.mono,
                fontSize: 10.5,
                color: colors.textDim,
                letterSpacing: "0.04em",
              }}
            >
              <StatusDot color={agent?.status === "online" ? colors.success : colors.textDim} />
              {(agent?.status ?? "offline").toUpperCase()}
            </span>
          </div>
        </div>
        <span
          style={{
            fontFamily: fonts.display,
            fontWeight: 700,
            fontSize: 13,
            letterSpacing: "0.22em",
            textTransform: "uppercase",
            color: "#8a7c96",
          }}
        >
          Iris Machine
        </span>
      </header>

      <div
        ref={scrollRef}
        style={{
          flex: "1 1 auto",
          overflowY: "auto",
          display: "flex",
          flexDirection: "column",
          gap: 20,
          padding: "28px clamp(20px, 4vw, 40px)",
          maxWidth: 780,
          width: "100%",
          margin: "0 auto",
        }}
      >
        {allMessages.map((msg) => {
          const isAgent = msg.author === "agent";
          return (
            <div
              key={msg.id}
              style={{
                display: "flex",
                gap: 12,
                justifyContent: isAgent ? "flex-start" : "flex-end",
                animation: "iris-fade-up 0.35s ease both",
              }}
            >
              {isAgent && agent?.avatarUrl ? (
                <div style={{ flex: "0 0 auto", width: 30, height: 30, borderRadius: 8, overflow: "hidden", marginTop: 2 }}>
                  <img src={agent.avatarUrl} alt={agent.name} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
                </div>
              ) : null}
              <div
                style={{
                  maxWidth: "72%",
                  padding: "12px 16px",
                  borderRadius: isAgent ? "4px 16px 16px 16px" : "16px 4px 16px 16px",
                  background: isAgent ? "rgba(255,255,255,0.04)" : colors.accent,
                  border: isAgent ? `1px solid ${colors.border}` : "none",
                  color: isAgent ? "#E7DCE9" : "#fff",
                  fontSize: 14.5,
                  lineHeight: 1.55,
                  whiteSpace: "pre-wrap",
                }}
              >
                {msg.content}
              </div>
            </div>
          );
        })}

        {sending ? (
          <div style={{ display: "flex", gap: 12 }}>
            <div
              style={{
                padding: "14px 16px",
                borderRadius: "4px 16px 16px 16px",
                background: "rgba(255,255,255,0.04)",
                border: `1px solid ${colors.border}`,
                display: "flex",
                gap: 5,
                alignItems: "center",
              }}
            >
              {[0, 0.2, 0.4].map((delay) => (
                <span
                  key={delay}
                  style={{
                    width: 6,
                    height: 6,
                    borderRadius: "50%",
                    background: "#b5a3c0",
                    animation: `iris-blink 1.2s infinite ${delay}s`,
                  }}
                />
              ))}
            </div>
          </div>
        ) : null}
      </div>

      <form
        onSubmit={handleSubmit}
        style={{
          flex: "0 0 auto",
          padding: "18px clamp(20px, 4vw, 40px) 26px",
          maxWidth: 780,
          width: "100%",
          margin: "0 auto",
        }}
      >
        {error ? (
          <div style={{ marginBottom: 10 }}>
            <Alert>{error}</Alert>
          </div>
        ) : null}
        <div
          style={{
            display: "flex",
            alignItems: "flex-end",
            gap: 10,
            padding: "10px 10px 10px 18px",
            borderRadius: 16,
            background: "rgba(255,255,255,0.04)",
            border: `1px solid rgba(255,255,255,0.1)`,
          }}
        >
          <textarea
            rows={1}
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={handleKeyDown}
            placeholder={`Envie uma mensagem para o ${agent?.name ?? "agente"}…`}
            style={{
              flex: "1 1 auto",
              resize: "none",
              background: "transparent",
              border: "none",
              outline: "none",
              color: colors.text,
              fontSize: 14.5,
              lineHeight: 1.5,
              padding: "6px 0",
              maxHeight: 120,
            }}
          />
          <button
            type="submit"
            disabled={sending || !input.trim()}
            className="iris-button-primary"
            style={{
              flex: "0 0 auto",
              width: 36,
              height: 36,
              borderRadius: 10,
              border: "none",
              background: colors.accent,
              color: "#fff",
              fontSize: 16,
              cursor: "pointer",
            }}
          >
            ↑
          </button>
        </div>
        <div
          style={{
            textAlign: "center",
            marginTop: 10,
            fontFamily: fonts.mono,
            fontSize: 10.5,
            color: "#59636d",
          }}
        >
          {agent?.name} pode cometer erros. Verifique informações importantes.
        </div>
      </form>
    </div>
  );
}

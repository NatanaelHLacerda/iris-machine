import { useEffect, useRef, useState, type FormEvent, type KeyboardEvent } from "react";
import { Link, useParams } from "react-router-dom";
import type { Agent, ChatMessage } from "@iris/shared";
import { agentsApi } from "@/features/agents/agents-api";
import { useAuth } from "@/features/auth/AuthContext";
import { FullScreenLoader } from "@/components/FullScreenLoader";

const sidebarLinkStyle = {
  display: "flex",
  alignItems: "center",
  gap: 12,
  padding: "10px 12px",
  borderRadius: 9,
  color: "#b0a2ba",
  fontSize: 14,
  fontFamily: "'Space Grotesk', sans-serif",
} as const;

export function ChatPage() {
  const { agentId = "jimmy" } = useParams();
  const { user } = useAuth();
  const [agent, setAgent] = useState<Agent | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [input, setInput] = useState("");
  const [isTyping, setIsTyping] = useState(false);
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
    requestAnimationFrame(() => {
      const node = scrollRef.current;
      if (node) node.scrollTop = node.scrollHeight;
    });
  }, [messages, isTyping]);

  async function send() {
    const text = input.trim();
    if (!text || isTyping) return;

    setInput("");
    setIsTyping(true);
    setError(null);
    try {
      const { messages: created } = await agentsApi.sendMessage(agentId, text);
      setMessages((prev) => [...prev, ...created]);
    } catch {
      setError("Falha ao enviar a mensagem.");
      setInput(text);
    } finally {
      setIsTyping(false);
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

  const greeting: ChatMessage[] = agent
    ? [
        {
          id: "__greeting",
          agentId,
          author: "agent",
          content: `Olá! Eu sou o ${agent.name}, especialista em ${agent.role.toLowerCase()}. Me envie um trecho ou descreva o que precisa migrar.`,
          createdAt: new Date().toISOString(),
        },
      ]
    : [];

  const avatar = agent?.avatarUrl ?? "/uploads/761a440f4d38e77c845b67badf122797.jpg";

  return (
    <div
      style={{
        height: "100vh",
        width: "100%",
        background: "#0B0910",
        color: "#F4EEF6",
        fontFamily: "'Space Grotesk', sans-serif",
        display: "grid",
        gridTemplateColumns: "240px 1fr",
        overflow: "hidden",
      }}
    >
      <aside
        style={{
          borderRight: "1px solid rgba(255,255,255,0.08)",
          display: "flex",
          flexDirection: "column",
          gap: 4,
          padding: "18px 12px",
        }}
      >
        <Link to="/painel" className="iris-nav-item" style={sidebarLinkStyle}>
          <span style={{ fontSize: 16, width: 18, textAlign: "center" }}>◧</span>Painel
        </Link>
        <Link to={`/agentes/${agentId}/configuracao`} className="iris-nav-item" style={sidebarLinkStyle}>
          <span style={{ fontSize: 16, width: 18, textAlign: "center" }}>⚙</span>Configurações
        </Link>

        <div
          style={{
            marginTop: "auto",
            display: "flex",
            alignItems: "center",
            gap: 10,
            padding: "10px 12px",
            borderRadius: 10,
          }}
        >
          <div style={{ width: 30, height: 30, borderRadius: "50%", overflow: "hidden", flex: "0 0 auto" }}>
            <img
              src={user?.avatarUrl ?? "/uploads/761a440f4d38e77c845b67badf122797.jpg"}
              alt="usuário"
              style={{ width: "100%", height: "100%", objectFit: "cover" }}
            />
          </div>
          <span
            style={{
              fontSize: 13,
              color: "#F4EEF6",
              overflow: "hidden",
              textOverflow: "ellipsis",
              whiteSpace: "nowrap",
            }}
          >
            {user?.name ?? user?.email}
          </span>
        </div>
      </aside>

      <div
        style={{
          display: "flex",
          flexDirection: "column",
          overflow: "hidden",
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
            borderBottom: "1px solid rgba(255,255,255,0.08)",
          }}
        >
          <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
            <div
              style={{
                position: "relative",
                width: 34,
                height: 34,
                borderRadius: 10,
                overflow: "hidden",
                boxShadow: "0 0 0 1px rgba(242,73,160,0.3)",
              }}
            >
              <img src={avatar} alt={agent?.name} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
            </div>
            <div style={{ display: "flex", flexDirection: "column", gap: 1 }}>
              <span style={{ fontWeight: 600, fontSize: 15, color: "#F4EEF6" }}>{agent?.name}</span>
              <span
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: 5,
                  fontFamily: "'IBM Plex Mono', monospace",
                  fontSize: 10.5,
                  color: "#7c8894",
                  letterSpacing: "0.04em",
                }}
              >
                <span
                  style={{
                    width: 6,
                    height: 6,
                    borderRadius: "50%",
                    background: "#7CF2A6",
                    boxShadow: "0 0 6px #7CF2A6",
                  }}
                />
                {(agent?.status ?? "offline").toUpperCase()}
              </span>
            </div>
          </div>
          <span
            style={{
              fontFamily: "'Orbitron', sans-serif",
              fontWeight: 700,
              fontSize: 13,
              letterSpacing: "0.22em",
              textTransform: "uppercase",
              color: "#8a7c96",
            }}
          >
            IRIS MACHINE
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
          {[...greeting, ...messages].map((msg) => {
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
                {isAgent ? (
                  <div
                    style={{ flex: "0 0 auto", width: 30, height: 30, borderRadius: 8, overflow: "hidden", marginTop: 2 }}
                  >
                    <img src={avatar} alt={agent?.name} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
                  </div>
                ) : null}
                <div
                  style={{
                    maxWidth: "72%",
                    display: "flex",
                    flexDirection: "column",
                    gap: 4,
                    alignItems: isAgent ? "flex-start" : "flex-end",
                  }}
                >
                  <div
                    style={{
                      padding: "12px 16px",
                      borderRadius: isAgent ? "4px 16px 16px 16px" : "16px 4px 16px 16px",
                      background: isAgent ? "rgba(255,255,255,0.04)" : "#F249A0",
                      border: isAgent ? "1px solid rgba(255,255,255,0.08)" : "none",
                      fontSize: 14.5,
                      lineHeight: 1.55,
                      color: isAgent ? "#E7DCE9" : "#ffffff",
                      whiteSpace: "pre-wrap",
                    }}
                  >
                    {msg.content}
                  </div>
                </div>
              </div>
            );
          })}

          {isTyping ? (
            <div style={{ display: "flex", gap: 12, justifyContent: "flex-start" }}>
              <div
                style={{ flex: "0 0 auto", width: 30, height: 30, borderRadius: 8, overflow: "hidden", marginTop: 2 }}
              >
                <img src={avatar} alt={agent?.name} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
              </div>
              <div
                style={{
                  padding: "14px 16px",
                  borderRadius: "4px 16px 16px 16px",
                  background: "rgba(255,255,255,0.04)",
                  border: "1px solid rgba(255,255,255,0.08)",
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
            boxSizing: "border-box",
          }}
        >
          {error ? (
            <div style={{ marginBottom: 10, fontSize: 12.5, color: "#F2617A", textAlign: "center" }}>{error}</div>
          ) : null}
          <div
            style={{
              display: "flex",
              alignItems: "flex-end",
              gap: 10,
              padding: "10px 10px 10px 18px",
              borderRadius: 16,
              background: "rgba(255,255,255,0.04)",
              border: "1px solid rgba(255,255,255,0.1)",
              backdropFilter: "blur(10px)",
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
                color: "#F4EEF6",
                fontFamily: "'Space Grotesk', sans-serif",
                fontSize: 14.5,
                lineHeight: 1.5,
                padding: "6px 0",
                maxHeight: 120,
              }}
            />
            <button
              type="submit"
              className="iris-button-primary"
              style={{
                flex: "0 0 auto",
                width: 36,
                height: 36,
                borderRadius: 10,
                border: "none",
                background: "#F249A0",
                color: "#ffffff",
                fontSize: 16,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
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
              fontFamily: "'IBM Plex Mono', monospace",
              fontSize: 10.5,
              color: "#59636d",
              letterSpacing: "0.03em",
            }}
          >
            {agent?.name} pode cometer erros. Verifique informações importantes.
          </div>
        </form>
      </div>
    </div>
  );
}

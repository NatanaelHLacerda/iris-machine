import { useCallback, useEffect, useRef, useState, type FormEvent, type KeyboardEvent } from "react";
import { useParams } from "react-router-dom";
import type { Agent, ChatMessage } from "@iris/shared";
import { agentsApi } from "@/features/agents/agents-api";
import { useAuth } from "@/features/auth/AuthContext";
import { FullScreenLoader } from "@/components/FullScreenLoader";
import { AppSidebar } from "@/components/AppSidebar";
import { colors, fonts } from "@/styles/theme";

export function ChatPage() {
  const { agentId = "jimmy" } = useParams();
  const { user } = useAuth();
  const [agent, setAgent] = useState<Agent | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [input, setInput] = useState("");
  const [isTyping, setIsTyping] = useState(false);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [sendError, setSendError] = useState<string | null>(null);
  const scrollRef = useRef<HTMLDivElement>(null);

  const load = useCallback(() => {
    let cancelled = false;
    setLoading(true);
    setLoadError(null);
    Promise.all([agentsApi.get(agentId), agentsApi.messages(agentId)])
      .then(([{ agent: a }, { messages: m }]) => {
        if (cancelled) return;
        setAgent(a);
        setMessages(m);
      })
      .catch(() => {
        if (!cancelled) setLoadError("Não foi possível carregar a conversa.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [agentId]);

  useEffect(() => load(), [load]);

  useEffect(() => {
    requestAnimationFrame(() => {
      const node = scrollRef.current;
      if (node) node.scrollTop = node.scrollHeight;
    });
  }, [messages, isTyping]);

  async function send() {
    const text = input.trim();
    if (!text || isTyping) return;

    // Otimista: a mensagem do usuário aparece na hora, sem esperar o
    // agente responder (podia levar até 2min e a mensagem sumia da tela
    // até lá — ver hermesClient.ts CHAT_TIMEOUT_MS).
    const tempId = `temp-${Date.now()}`;
    const optimisticMessage: ChatMessage = {
      id: tempId,
      agentId,
      author: "user",
      content: text,
      createdAt: new Date().toISOString(),
    };

    setInput("");
    setSendError(null);
    setMessages((prev) => [...prev, optimisticMessage]);
    setIsTyping(true);
    try {
      const { messages: created } = await agentsApi.sendMessage(agentId, text);
      setMessages((prev) => [...prev.filter((m) => m.id !== tempId), ...created]);
    } catch {
      setSendError("Falha ao enviar a mensagem.");
      setMessages((prev) => prev.filter((m) => m.id !== tempId));
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

  if (loadError) {
    return (
      <div
        style={{
          height: "100vh",
          display: "flex",
          flexDirection: "column",
          alignItems: "center",
          justifyContent: "center",
          gap: 14,
          background: colors.bg,
          color: colors.text,
          fontFamily: fonts.body,
        }}
      >
        <span role="alert" style={{ color: colors.danger, fontSize: 14 }}>
          {loadError}
        </span>
        <button
          type="button"
          onClick={load}
          className="iris-button-primary"
          style={{
            padding: "9px 16px",
            borderRadius: 8,
            border: "none",
            background: colors.accent,
            color: "#fff",
            fontWeight: 600,
            fontSize: 13,
            cursor: "pointer",
          }}
        >
          Tentar novamente
        </button>
      </div>
    );
  }

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
        background: colors.bg,
        color: colors.text,
        fontFamily: fonts.body,
        display: "grid",
        gridTemplateColumns: "240px 1fr",
        overflow: "hidden",
      }}
    >
      <AppSidebar
        links={[
          { to: "/painel", icon: "◧", label: "Painel", active: false },
          { to: `/agentes/${agentId}/configuracao`, icon: "⚙", label: "Configurações", active: false },
        ]}
        userName={user?.name ?? user?.email ?? ""}
        userAvatar={user?.avatarUrl ?? "/uploads/761a440f4d38e77c845b67badf122797.jpg"}
      />

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
            borderBottom: `1px solid ${colors.border}`,
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
              <span style={{ fontWeight: 600, fontSize: 15, color: colors.text }}>{agent?.name}</span>
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
              fontFamily: fonts.display,
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
          role="log"
          aria-live="polite"
          aria-label="Mensagens da conversa"
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
                      background: isAgent ? "rgba(255,255,255,0.04)" : colors.accent,
                      border: isAgent ? `1px solid ${colors.border}` : "none",
                      fontSize: 14.5,
                      lineHeight: 1.55,
                      color: isAgent ? "#E7DCE9" : "#ffffff",
                      whiteSpace: "pre-wrap",
                      opacity: msg.id.startsWith("temp-") ? 0.7 : 1,
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
                aria-label={`${agent?.name ?? "Agente"} está digitando`}
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
            boxSizing: "border-box",
          }}
        >
          {sendError ? (
            <div role="alert" style={{ marginBottom: 10, fontSize: 12.5, color: colors.danger, textAlign: "center" }}>
              {sendError}
            </div>
          ) : null}
          <div
            style={{
              display: "flex",
              alignItems: "flex-end",
              gap: 10,
              padding: "10px 10px 10px 18px",
              borderRadius: 16,
              background: colors.bgSubtle,
              border: `1px solid ${colors.borderStrong}`,
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
                color: colors.text,
                fontFamily: fonts.body,
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
                background: colors.accent,
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
              fontFamily: fonts.mono,
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

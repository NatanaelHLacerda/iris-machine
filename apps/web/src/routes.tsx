import { createBrowserRouter, Navigate } from "react-router-dom";
import { ProtectedRoute } from "@/features/auth/ProtectedRoute";
import { LandingPage } from "@/pages/LandingPage";
import { DashboardPage } from "@/pages/DashboardPage";
import { ChatPage } from "@/pages/ChatPage";
import { AgentConfigPage } from "@/pages/AgentConfigPage";

// Cada tela desenha o próprio layout, como nos protótipos — o painel e a
// conversa têm sidebars distintas e a configuração não tem sidebar nenhuma.
export const router = createBrowserRouter([
  { path: "/", element: <LandingPage /> },
  {
    element: <ProtectedRoute />,
    children: [
      { path: "/painel", element: <DashboardPage /> },
      { path: "/agentes/:agentId/conversa", element: <ChatPage /> },
      { path: "/agentes/:agentId/configuracao", element: <AgentConfigPage /> },
    ],
  },
  { path: "*", element: <Navigate to="/" replace /> },
]);

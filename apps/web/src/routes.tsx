import { createBrowserRouter, Navigate } from "react-router-dom";
import { AppLayout } from "@/components/AppLayout";
import { ProtectedRoute } from "@/features/auth/ProtectedRoute";
import { LandingPage } from "@/pages/LandingPage";
import { DashboardPage } from "@/pages/DashboardPage";
import { ChatPage } from "@/pages/ChatPage";
import { AgentConfigPage } from "@/pages/AgentConfigPage";

export const router = createBrowserRouter([
  { path: "/", element: <LandingPage /> },
  {
    element: <ProtectedRoute />,
    children: [
      {
        element: <AppLayout />,
        children: [
          { path: "/painel", element: <DashboardPage /> },
          { path: "/agentes/:agentId/conversa", element: <ChatPage /> },
          { path: "/agentes/:agentId/configuracao", element: <AgentConfigPage /> },
        ],
      },
    ],
  },
  { path: "*", element: <Navigate to="/" replace /> },
]);

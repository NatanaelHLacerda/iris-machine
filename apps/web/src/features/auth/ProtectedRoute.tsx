import { Navigate, Outlet, useLocation } from "react-router-dom";
import { useAuth } from "./AuthContext";
import { FullScreenLoader } from "@/components/FullScreenLoader";

/** Só deixa passar com sessão válida; guarda a rota tentada para voltar após o login. */
export function ProtectedRoute() {
  const { user, loading } = useAuth();
  const location = useLocation();

  if (loading) return <FullScreenLoader />;
  if (!user) return <Navigate to="/" replace state={{ from: location.pathname }} />;

  return <Outlet />;
}

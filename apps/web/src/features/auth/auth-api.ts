import type {
  AuthResponse,
  AuthUser,
  SignInInput,
  SignUpInput,
} from "@iris/shared";
import { apiFetch } from "@/lib/api";

type SignUpResponse = AuthResponse & { emailConfirmationRequired?: boolean };

export const authApi = {
  signIn: (input: SignInInput) =>
    apiFetch<AuthResponse>("/auth/signin", { method: "POST", body: input }),

  signUp: (input: SignUpInput) =>
    apiFetch<SignUpResponse>("/auth/signup", { method: "POST", body: input }),

  signOut: () => apiFetch<void>("/auth/signout", { method: "POST" }),

  me: () => apiFetch<{ user: AuthUser }>("/auth/me"),

  requestPasswordReset: (email: string) =>
    apiFetch<void>("/auth/password/reset", { method: "POST", body: { email } }),

  updatePassword: (password: string) =>
    apiFetch<{ user: AuthUser }>("/auth/password", { method: "PATCH", body: { password } }),
};

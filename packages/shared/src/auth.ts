import { z } from "zod";

export const emailSchema = z
  .string({ required_error: "E-mail é obrigatório" })
  .trim()
  .min(1, "E-mail é obrigatório")
  .email("E-mail inválido");

export const passwordSchema = z
  .string({ required_error: "Senha é obrigatória" })
  .min(8, "Senha deve ter ao menos 8 caracteres")
  .max(72, "Senha deve ter no máximo 72 caracteres");

export const signUpSchema = z.object({
  email: emailSchema,
  password: passwordSchema,
  name: z.string().trim().min(1, "Nome é obrigatório").max(120).optional(),
});

export const signInSchema = z.object({
  email: emailSchema,
  password: z.string().min(1, "Senha é obrigatória"),
});

export const requestPasswordResetSchema = z.object({
  email: emailSchema,
});

export const updatePasswordSchema = z.object({
  password: passwordSchema,
});

export type SignUpInput = z.infer<typeof signUpSchema>;
export type SignInInput = z.infer<typeof signInSchema>;
export type RequestPasswordResetInput = z.infer<typeof requestPasswordResetSchema>;
export type UpdatePasswordInput = z.infer<typeof updatePasswordSchema>;

export interface AuthUser {
  id: string;
  email: string | null;
  name: string | null;
  avatarUrl: string | null;
  role: string;
  createdAt: string;
}

export interface SessionTokens {
  accessToken: string;
  expiresAt: number | null;
}

export interface AuthResponse {
  user: AuthUser;
  session: SessionTokens | null;
}

export interface ApiError {
  error: {
    code: string;
    message: string;
    details?: unknown;
  };
}

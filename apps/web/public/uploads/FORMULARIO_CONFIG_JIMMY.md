# Formulário — Configurar Jimmy

> Spec funcional para design. Cobre apenas configuração do **agente** (persistente). Não inclui dados de tarefa (tela separada, "Nova tarefa").

Tela única, 4 seções (cards), nesta ordem. Rodapé fixo: **Cancelar** / **Salvar alterações**.

---

## Seção 1 — Identidade (somente exibição)

Nome do agente, função/especialidade e instruções de comportamento vêm de `SOUL.md` — nenhum deles é editável aqui, só exibidos para confirmar qual agente está sendo configurado.

Design: visualmente diferenciar da seção editável (ex: fundo acinzentado, ícone de cadeado, sem borda de input).

---

## Seção 2 — Conexão

| Campo | Tipo | Editável | Obrigatório |
|---|---|---|---|
| Modelo | texto, somente exibição | Não | — |
| Endereço VPS | texto curto | Sim | Sim |
| Ativo | toggle | Sim | — |

Modelo vem de `config.yaml`/programação do agente, mesma lógica da Seção 1 — não é campo editável.

---

## Seção 3 — GitHub

| Campo | Tipo | Obrigatório | Ajuda |
|---|---|---|---|
| Token de acesso (PAT) | senha, write-only | Sim | Escopo `repo` (+ `workflow` se usar CI/CD) |
| Repositório protótipo | URL | Sim | Referência, somente leitura |
| Repositório destino | URL | Sim | Onde o Jimmy escreve, precisa de permissão de escrita |

Design:
- Token: nunca reexibido após salvo (mostra `••••••••` + opção "Substituir"), sem copiar/colar visível, ausente de qualquer resumo/preview da config.
- Sem campo de branch (gerado automaticamente por tarefa: `jimmy/<feature>`).
- Sem usuário/e-mail Git (não fazem parte desta config).

---

## Seção 4 — Convenções do projeto destino

Accordion (subseções colapsáveis). Alimenta o contexto que o Jimmy consulta antes de codar.

### 4.1 Identificação
| Campo | Tipo | Obrigatório |
|---|---|---|
| Nome do projeto | texto curto | Sim |
| Stack | select + "Outro" — Next.js+TS / React+Vite / React Native+Expo | Sim |

*Repositório = mesmo valor de "Repositório destino" (Seção 3), não perguntar de novo. Data do último escaneamento é automática, não é campo.*

### 4.2 Gerenciamento de estado
| Campo | Tipo | Obrigatório |
|---|---|---|
| Padrão adotado | select + "Outro" — Context API / Redux / Zustand / React Query | Não |
| Convenção de organização | texto curto | Não |

### 4.3 Navegação/rotas
| Campo | Tipo | Obrigatório |
|---|---|---|
| Mecanismo | select + "Outro" — App Router / React Router / React Navigation | Não |
| Registro de rotas | texto curto | Não |
| Transições/animações customizadas | texto curto | Não |

### 4.4 Estrutura de pastas
| Campo | Tipo | Obrigatório |
|---|---|---|
| Padrão | select + "Outro" — por feature / por tipo / colocation | Não |
| Design system central (caminho) | texto curto | Não |
| Modelos de dados/tipos (caminho) | texto curto | Não |
| Serviços/API (caminho + cliente) | texto curto | Não |

### 4.5 Estilo e nomenclatura
| Campo | Tipo | Obrigatório |
|---|---|---|
| Nome de arquivo/pasta | texto curto | Não |
| Nome de componente | texto curto | Não |
| Estilos | select + "Outro" — Tailwind / CSS Modules / styled-components | Não |
| Tipografia (fonte + onde configurada) | texto curto | Não |

### 4.6 Componentes de design system existentes
Tabela dinâmica (adicionar/remover linha): **Componente | Localização | Uso típico**

### 4.7 Rede e dados
| Campo | Tipo | Obrigatório |
|---|---|---|
| Cliente HTTP | select + "Outro" — fetch / axios / react-query / swr | Não |
| Padrão de tratamento de erro de API | texto curto | Não |

### 4.8 Dependências já presentes
Lista de tags (chips), adicionar por texto livre. Não obrigatório.

### 4.9 Observações adicionais
Textarea livre. Não obrigatório.

---

## Regras gerais

- Campos não obrigatórios podem ficar vazios, mas um item vazio na Seção 4 sinaliza badge "incompleto" — a ausência faz o Jimmy pausar tarefas reais pedindo esclarecimento.
- "Ativo" só pode ser ligado com Endereço VPS + Seção 3 completos (Seção 1 e o campo Modelo não entram nessa checagem, por não serem editáveis).

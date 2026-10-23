# SPDD — Gestão CRM

```text
requirements/REQ-XXX   (semente)
   ↓
spdd/analysis/REQ-XXX  (análise)
   ↓
spdd/prompt/REQ-XXX    (REASONS Canvas — quality gate)
   ↓
implementação          (testes de aceite primeiro, depois código)
   ↓
validação              (php artisan test · pint · npm run build · tela no navegador)
```

## Regras

- análise não autoriza implementação;
- REASONS só autoriza implementação com status `APROVADO` dado por humano;
- decisão pendente (`DP-XXYY`) não é escolhida pelo agente;
- mudança funcional atualiza o REASONS antes do código; refatoração sem mudança funcional sincroniza o REASONS depois;
- sem `v2`/`final`: o mesmo arquivo evolui, o Git guarda o histórico;
- não passam pela cadeia: ajuste de cor, espaçamento, texto e correção de estilo.

## Normas comuns a todos os REASONS

Valem para toda a cadeia; cada canvas só registra o que for diferente.

- **Stack:** Laravel 13, PHP 8.5, Blade, Vite, sem dependência nova (Composer ou npm) sem aprovação.
- **Rotas:** prefixo `/gestao-crm`, nomes `crm.*`, middleware `auth` (exceto login).
- **Código:** `App\Http\Controllers\Crm\*`, `App\Models\*`, regras de cálculo em `App\Support\Crm\*`; views em `resources/views/crm/`; validação em Form Requests.
- **Dinheiro:** inteiro em centavos (`*_cents`), formatado `R$ 1.234,56` só na view.
- **Datas:** `America/Sao_Paulo` na exibição; armazenadas em UTC.
- **Visual:** identidade da landing (`resources/css/app.css`): fundo `#0B0910`, violeta `#7B6CF6` para estrutura, rosa `#D6246E` só para ação/destaque, Space Grotesk títulos, Inter corpo, JetBrains Mono para UTM/códigos, Orbitron só no logo. CSS do CRM em `resources/css/crm.css`, JS em `resources/js/crm.js`.
- **Estrutura de tela** (herdada de `legalizai-projeto/crm-frontend`): sidebar escura por grupos, barra da tela (eyebrow, título, total, busca, filtros), números de resumo, tabela/board, dossiê.
- **Dado ausente** aparece como "—", nunca como zero inventado.
- **Testes:** PHPUnit, feature tests com factories; `vendor/bin/pint --dirty` ao final.

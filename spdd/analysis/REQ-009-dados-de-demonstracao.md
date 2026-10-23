# Análise — REQ-009 Dados de demonstração
**Status:** APROVADO — por delegação (semente aprovada em 2026-10-23)

## Conceitos

- **Entidades:** todas as dos REQs 002–007.
- **Operações:** gerar cenário temporal coerente, proteger produção, idempotência.

## O que existe

| Item | Estado |
|---|---|
| `database/seeders/DatabaseSeeder.php` | Padrão do Laravel (cria `Test User`) |
| Factories | `UserFactory`; as demais nascem nos REQs 002–007 |
| Banco local | SQLite |

## Riscos

- **Incoerência entre camadas:** gerar cada tabela com factory isolada produz um funil impossível (mais clientes do que leads). Gerar de cima para baixo, dia a dia, derivando cada camada da anterior com taxas definidas.
- **Volume no SQLite:** 6 meses × ~150 visitas/dia ≈ 27 mil sessões e ~80 mil eventos; inserir em lote (`insert` de 1.000 linhas) e desligar eventos de model.
- **Aleatoriedade:** usar seed fixa (`mt_srand`/`fake()->seed`) para que todos vejam o mesmo cenário.
- **Produção:** guarda explícita de ambiente no comando e no seeder.

## Direções de design

1. **`DemoScenario` com parâmetros por mês (visitas, taxas, gasto) + simulação diária** (recomendada): coerente, reprodutível, fácil de ajustar a "história".
2. Factories soltas com quantidades fixas: rápido, mas sem coerência de funil e de ROAS.

## Impacto

Seeder, comando e classe de cenário; nenhum código de produção alterado.

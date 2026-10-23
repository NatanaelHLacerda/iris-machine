<?php

namespace App\Support\Crm;

use InvalidArgumentException;

/**
 * Único lugar que converte reais ↔ centavos. Dinheiro nunca vira float.
 */
class Money
{
    /**
     * Aceita "1.234,56", "1234,56", "1234.56" e "1234".
     */
    public static function toCents(string|int|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value * 100;
        }

        $clean = preg_replace('/[^\d,.\-]/', '', $value);

        if (str_contains($clean, ',')) {
            $clean = str_replace(['.', ','], ['', '.'], $clean);
        } elseif (substr_count($clean, '.') > 1 || preg_match('/\.\d{3}$/', $clean)) {
            $clean = str_replace('.', '', $clean);
        }

        if (! preg_match('/^-?\d+(\.\d{1,2})?$/', $clean)) {
            throw new InvalidArgumentException("Valor inválido: {$value}");
        }

        [$units, $decimals] = array_pad(explode('.', ltrim($clean, '-')), 2, '0');
        $cents = (int) $units * 100 + (int) str_pad($decimals, 2, '0');

        return str_starts_with($clean, '-') ? -$cents : $cents;
    }

    public static function format(?int $cents, bool $withSymbol = true): string
    {
        if ($cents === null) {
            return '—';
        }

        $formatted = number_format(abs($cents) / 100, 2, ',', '.');

        return ($cents < 0 ? '−' : '').($withSymbol ? 'R$ ' : '').$formatted;
    }

    /**
     * Versão curta para KPIs: R$ 3,9 mil.
     */
    public static function short(?int $cents): string
    {
        if ($cents === null) {
            return '—';
        }

        if (abs($cents) < 1_000_000) {
            return self::format($cents);
        }

        return ($cents < 0 ? '−' : '').'R$ '.number_format(abs($cents) / 100_000, 1, ',', '.').' mil';
    }
}

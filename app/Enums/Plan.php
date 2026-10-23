<?php

namespace App\Enums;

/**
 * Formatos vendidos; valores iguais aos slugs de config('iris.plans').
 */
enum Plan: string
{
    case Sessao = 'sessao';
    case Mentoria = 'mentoria';
    case Squad = 'squad';

    public function label(): string
    {
        return $this->config()['name'];
    }

    /**
     * Preço de tabela em centavos; null quando é sob consulta.
     */
    public function priceCents(): ?int
    {
        return $this->config()['price_cents'];
    }

    public function isMonthly(): bool
    {
        return $this->config()['billing'] === 'monthly';
    }

    /**
     * @return array{slug: string, name: string, price_cents: ?int, billing: string}
     */
    private function config(): array
    {
        return collect(config('iris.plans'))->firstWhere('slug', $this->value);
    }
}

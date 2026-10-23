<?php

namespace App\Support\Crm\Data;

/**
 * Valor do período com o do período anterior. Formatos: money, int, percent, multiplier.
 */
final readonly class Kpi
{
    public function __construct(
        public string $label,
        public int|float|null $value,
        public int|float|null $previous = null,
        public string $format = 'int',
        public ?string $hint = null,
        public bool $lowerIsBetter = false,
    ) {}

    public function deltaPercent(): ?float
    {
        if ($this->value === null || $this->previous === null || (float) $this->previous === 0.0) {
            return null;
        }

        return ($this->value - $this->previous) / abs($this->previous);
    }

    public function isImprovement(): ?bool
    {
        $delta = $this->deltaPercent();

        if ($delta === null || $delta === 0.0) {
            return null;
        }

        return $this->lowerIsBetter ? $delta < 0 : $delta > 0;
    }
}

<?php

namespace App\Support\Crm\Data;

final readonly class FunnelStep
{
    public function __construct(
        public string $key,
        public string $label,
        public int $count,
        public ?float $rateFromPrevious,
        public bool $isBottleneck = false,
    ) {}

    public function withBottleneck(): self
    {
        return new self($this->key, $this->label, $this->count, $this->rateFromPrevious, true);
    }
}

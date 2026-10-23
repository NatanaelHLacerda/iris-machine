<?php

namespace Database\Factories;

use App\Enums\Channel;
use App\Enums\Plan;
use App\Models\Lead;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'plan' => Plan::Sessao,
            'amount_cents' => 12990,
            'is_recurring' => false,
            'sold_at' => now(),
            'channel' => Channel::Direto,
        ];
    }

    public function mentoria(): static
    {
        return $this->state(['plan' => Plan::Mentoria, 'amount_cents' => 39990, 'is_recurring' => true]);
    }
}

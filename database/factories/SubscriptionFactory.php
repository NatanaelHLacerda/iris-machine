<?php

namespace Database\Factories;

use App\Enums\Plan;
use App\Enums\SubscriptionStatus;
use App\Models\Sale;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory()->mentoria(),
            'lead_id' => fn (array $attributes) => Sale::find($attributes['sale_id'])->lead_id,
            'plan' => Plan::Mentoria,
            'monthly_amount_cents' => 39990,
            'billing_day' => 10,
            'status' => SubscriptionStatus::Active,
            'started_at' => now(),
        ];
    }

    public function canceled(?\DateTimeInterface $at = null): static
    {
        return $this->state(['status' => SubscriptionStatus::Canceled, 'canceled_at' => $at ?? now()]);
    }
}

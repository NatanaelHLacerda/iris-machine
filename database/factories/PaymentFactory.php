<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'lead_id' => fn (array $attributes) => Sale::find($attributes['sale_id'])->lead_id,
            'amount_cents' => 12990,
            'due_date' => now()->toDateString(),
            'status' => PaymentStatus::Pending,
        ];
    }

    public function paid(?\DateTimeInterface $at = null): static
    {
        return $this->state(['status' => PaymentStatus::Paid, 'paid_at' => $at ?? now(), 'method' => PaymentMethod::Pix]);
    }
}

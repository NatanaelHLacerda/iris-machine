<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadActivity>
 */
class LeadActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'type' => ActivityType::Note,
            'data' => ['text' => fake()->sentence()],
        ];
    }
}

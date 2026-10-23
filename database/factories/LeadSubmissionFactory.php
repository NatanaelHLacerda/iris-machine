<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadSubmission>
 */
class LeadSubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'payload' => ['name' => fake()->name(), 'email' => fake()->safeEmail()],
        ];
    }
}

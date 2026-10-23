<?php

namespace Database\Factories;

use App\Enums\Channel;
use App\Enums\LeadLevel;
use App\Enums\LeadStatus;
use App\Enums\Plan;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'whatsapp' => '119'.fake()->numerify('########'),
            'level' => fake()->randomElement(LeadLevel::cases()),
            'plan_interest' => fake()->randomElement([...Plan::cases(), null]),
            'goal' => fake()->optional()->sentence(),
            'status' => LeadStatus::Novo,
            'channel' => Channel::Direto,
            'visitor_id' => (string) Str::uuid(),
            'last_submitted_at' => now(),
        ];
    }

    public function fromMeta(string $campaign = 'vibe-coding-junior', ?string $adset = null, ?string $ad = null): static
    {
        return $this->state([
            'channel' => Channel::Meta,
            'utm_source' => 'meta',
            'utm_medium' => 'paid',
            'utm_campaign' => $campaign,
            'utm_content' => $adset,
            'utm_term' => $ad,
        ]);
    }

    public function status(LeadStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}

<?php

namespace Database\Factories;

use App\Models\AdSpend;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdSpend>
 */
class AdSpendFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now('America/Sao_Paulo')->toDateString(),
            'platform' => 'meta',
            'campaign' => 'vibe-coding-junior',
            'campaign_key' => 'vibe-coding-junior',
            'adset_key' => '',
            'ad_key' => '',
            'spend_cents' => 5000,
            'impressions' => 4000,
            'link_clicks' => 80,
            'source' => 'manual',
        ];
    }

    public function campaign(string $name): static
    {
        return $this->state(['campaign' => $name, 'campaign_key' => mb_strtolower($name)]);
    }
}

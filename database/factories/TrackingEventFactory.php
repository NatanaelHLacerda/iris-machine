<?php

namespace Database\Factories;

use App\Enums\Channel;
use App\Enums\EventType;
use App\Models\TrackingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrackingEvent>
 */
class TrackingEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => EventType::PageView,
            'visitor_id' => (string) Str::uuid(),
            'session_id' => (string) Str::uuid(),
            'path' => '/',
            'channel' => Channel::Direto,
        ];
    }

    public function type(EventType $type, ?string $target = null): static
    {
        return $this->state(['type' => $type, 'target' => $target]);
    }
}

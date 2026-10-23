<?php

namespace Tests\Feature;

use App\Enums\Channel;
use App\Enums\EventType;
use App\Models\TrackingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StoreTrackingEventsTest extends TestCase
{
    use RefreshDatabase;

    private const string BROWSER = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36';

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @param  array<string, mixed>  $attribution
     * @return array<string, mixed>
     */
    private function batch(array $events, array $attribution = []): array
    {
        return [
            'visitor_id' => '7f1f5f3e-2f5b-4d55-9a49-3f1c8f2c9d10',
            'session_id' => '0d6b2c55-9a8e-4b1f-8f6f-2a9d8c7b6e54',
            'attribution' => $attribution,
            'events' => $events,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(array $payload, string $origin = 'http://localhost', string $userAgent = self::BROWSER): TestResponse
    {
        config(['app.url' => 'http://localhost']);

        return $this->postJson(route('tracking.store'), $payload, ['Origin' => $origin, 'User-Agent' => $userAgent]);
    }

    public function test_it_stores_each_valid_event(): void
    {
        $this->send($this->batch([
            ['type' => 'page_view', 'path' => '/'],
            ['type' => 'cta_click', 'target' => 'hero', 'path' => '/'],
        ], ['utm_source' => 'meta', 'utm_campaign' => 'vibe-coding-junior']))->assertNoContent();

        $this->assertSame(2, TrackingEvent::count());
        $click = TrackingEvent::firstWhere('type', EventType::CtaClick);
        $this->assertSame('hero', $click->target);
        $this->assertSame(Channel::Meta, $click->channel);
        $this->assertSame('vibe-coding-junior', $click->utm_campaign);
    }

    public function test_unknown_event_types_are_dropped(): void
    {
        $this->send($this->batch([
            ['type' => 'page_view', 'path' => '/'],
            ['type' => 'hack', 'path' => '/'],
        ]))->assertNoContent();

        $this->assertSame(1, TrackingEvent::count());
    }

    public function test_batches_over_twenty_events_are_rejected(): void
    {
        $this->send($this->batch(array_fill(0, 21, ['type' => 'page_view', 'path' => '/'])))
            ->assertUnprocessable();

        $this->assertSame(0, TrackingEvent::count());
    }

    public function test_bots_are_ignored(): void
    {
        $this->send($this->batch([['type' => 'page_view', 'path' => '/']]), userAgent: 'Googlebot/2.1')
            ->assertNoContent();

        $this->assertSame(0, TrackingEvent::count());
    }

    public function test_foreign_origins_are_forbidden(): void
    {
        $this->send($this->batch([['type' => 'page_view', 'path' => '/']]), origin: 'https://evil.example')
            ->assertForbidden();
    }
}

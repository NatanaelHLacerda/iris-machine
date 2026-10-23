<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Http\Requests\StoreTrackingEventsRequest;
use App\Models\TrackingEvent;
use App\Support\Crm\BotDetector;
use App\Support\Crm\ChannelClassifier;
use Illuminate\Http\Response;

class StoreTrackingEventsController extends Controller
{
    public function __invoke(StoreTrackingEventsRequest $request): Response
    {
        if (BotDetector::isBot($request->userAgent())) {
            return response()->noContent();
        }

        $attribution = $request->validated('attribution') ?? [];
        $channel = ChannelClassifier::classify($attribution['utm_source'] ?? null, $attribution['fbclid'] ?? null, $attribution['referrer'] ?? null);
        $ipHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $now = now();

        $rows = collect($request->validated('events'))
            ->filter(fn (array $event) => EventType::tryFrom($event['type']) !== null)
            ->map(fn (array $event) => [
                'type' => $event['type'],
                'target' => $event['target'] ?? null,
                'visitor_id' => $request->validated('visitor_id'),
                'session_id' => $request->validated('session_id'),
                'path' => $event['path'],
                'channel' => $channel->value,
                'utm_source' => $attribution['utm_source'] ?? null,
                'utm_campaign' => $attribution['utm_campaign'] ?? null,
                'utm_content' => $attribution['utm_content'] ?? null,
                'utm_term' => $attribution['utm_term'] ?? null,
                'ip_hash' => $ipHash,
                'created_at' => $now,
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            TrackingEvent::insert($rows);
        }

        return response()->noContent();
    }
}

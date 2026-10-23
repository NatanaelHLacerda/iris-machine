<?php

namespace App\Models;

use App\Enums\Channel;
use App\Enums\EventType;
use Database\Factories\TrackingEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'type', 'target', 'visitor_id', 'session_id', 'path', 'channel',
    'utm_source', 'utm_campaign', 'utm_content', 'utm_term', 'ip_hash', 'created_at',
])]
class TrackingEvent extends Model
{
    /** @use HasFactory<TrackingEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'channel' => Channel::class,
        ];
    }
}

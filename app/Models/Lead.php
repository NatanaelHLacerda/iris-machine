<?php

namespace App\Models;

use App\Enums\Channel;
use App\Enums\LeadLevel;
use App\Enums\LeadStatus;
use App\Enums\Plan;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'email', 'whatsapp', 'level', 'plan_interest', 'goal', 'status', 'channel',
    'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'referrer', 'landing_path',
    'visitor_id', 'last_submitted_at',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * Campos de origem first-touch: gravados na criação e nunca sobrescritos.
     */
    public const array ATTRIBUTION_FIELDS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'referrer', 'landing_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => LeadLevel::class,
            'plan_interest' => Plan::class,
            'status' => LeadStatus::class,
            'channel' => Channel::class,
            'last_submitted_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<LeadSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(LeadSubmission::class);
    }

    public function whatsappLink(): ?string
    {
        return $this->whatsapp ? 'https://wa.me/55'.$this->whatsapp : null;
    }

    public function formattedWhatsapp(): ?string
    {
        $digits = (string) $this->whatsapp;

        return match (strlen($digits)) {
            11 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7)),
            10 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6)),
            0 => null,
            default => $digits,
        };
    }
}

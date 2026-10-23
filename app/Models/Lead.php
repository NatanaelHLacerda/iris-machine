<?php

namespace App\Models;

use App\Enums\Channel;
use App\Enums\LeadLevel;
use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Enums\Plan;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'email', 'whatsapp', 'level', 'plan_interest', 'goal', 'status', 'channel',
    'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'referrer', 'landing_path',
    'visitor_id', 'last_submitted_at',
    'status_changed_at', 'next_action', 'next_action_at', 'meeting_at', 'lost_reason', 'lost_note',
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
            'status_changed_at' => 'datetime',
            'next_action_at' => 'datetime',
            'meeting_at' => 'datetime',
            'lost_reason' => LostReason::class,
        ];
    }

    /**
     * @return HasMany<LeadSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(LeadSubmission::class);
    }

    /**
     * @return HasMany<LeadActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Próxima ação com data vencida.
     */
    public function isOverdue(): bool
    {
        return $this->status->isOpen() && $this->next_action_at?->isPast() === true;
    }

    /**
     * Lead novo há mais de 24h sem ninguém falar com ele.
     */
    public function isCooling(): bool
    {
        return $this->status === LeadStatus::Novo && $this->created_at->lt(now()->subDay());
    }

    public function daysInStatus(): int
    {
        return (int) ($this->status_changed_at ?? $this->created_at)->diffInDays(now());
    }

    /**
     * @param  Builder<Lead>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereNotIn('status', [LeadStatus::Fechado, LeadStatus::Perdido])
            ->whereNotNull('next_action_at')
            ->where('next_action_at', '<', now());
    }

    /**
     * @param  Builder<Lead>  $query
     */
    #[Scope]
    protected function cooling(Builder $query): void
    {
        $query->where('status', LeadStatus::Novo)->where('created_at', '<', now()->subDay());
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

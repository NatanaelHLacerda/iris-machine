<?php

namespace App\Support\Crm;

use App\Enums\Channel;
use App\Enums\LeadLevel;
use App\Enums\LeadStatus;
use App\Enums\Plan;
use App\Models\Lead;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filtros da lista de leads, reutilizados pela exportação e pelos atalhos do dashboard.
 */
class LeadFilters
{
    public const array KEYS = ['q', 'status', 'channel', 'campaign', 'level', 'plan', 'from', 'to', 'flag'];

    /**
     * @return array<string, string>
     */
    public static function fromRequest(Request $request): array
    {
        return array_filter($request->only(self::KEYS), fn ($value) => is_string($value) && $value !== '');
    }

    /**
     * @param  Builder<Lead>  $query
     * @param  array<string, string>  $filters
     * @return Builder<Lead>
     */
    public static function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                $digits = preg_replace('/\D/', '', $term);

                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->when($digits !== '', fn (Builder $query) => $query->orWhere('whatsapp', 'like', "%{$digits}%")));
            })
            ->when(LeadStatus::tryFrom($filters['status'] ?? ''), fn (Builder $query, LeadStatus $status) => $query->where('status', $status))
            ->when(Channel::tryFrom($filters['channel'] ?? ''), fn (Builder $query, Channel $channel) => $query->where('channel', $channel))
            ->when($filters['campaign'] ?? null, fn (Builder $query, string $campaign) => $query->where('utm_campaign', $campaign))
            ->when(LeadLevel::tryFrom($filters['level'] ?? ''), fn (Builder $query, LeadLevel $level) => $query->where('level', $level))
            ->when(Plan::tryFrom($filters['plan'] ?? ''), fn (Builder $query, Plan $plan) => $query->where('plan_interest', $plan))
            ->when(self::date($filters['from'] ?? null), fn (Builder $query, CarbonImmutable $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when(self::date($filters['to'] ?? null), fn (Builder $query, CarbonImmutable $to) => $query->where('created_at', '<=', $to->endOfDay()))
            ->when(($filters['flag'] ?? null) === 'atrasados', fn (Builder $query) => $query->overdue())
            ->when(($filters['flag'] ?? null) === 'esfriando', fn (Builder $query) => $query->cooling());
    }

    private static function date(?string $value): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value, Period::TIMEZONE)?->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}

<?php

namespace App\Support\Crm;

use App\Enums\ActivityType;
use App\Enums\EventType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\TrackingEvent;
use App\Support\Crm\Data\FunnelStep;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Topo do funil em sessões (eventos da landing); do lead em diante, leads criados no período e até onde chegaram.
 */
class FunnelMetrics
{
    private const array TRACKED_STEPS = [
        'visitas' => [EventType::PageView, 'Visitas'],
        'cta' => [EventType::CtaClick, 'Clicou no CTA'],
        'form_view' => [EventType::FormView, 'Viu o formulário'],
        'form_start' => [EventType::FormStart, 'Começou a preencher'],
    ];

    /**
     * @param  array{channel?: ?string, campaign?: ?string}  $filters
     * @return Collection<int, FunnelStep>
     */
    public function steps(CarbonPeriod $period, array $filters = []): Collection
    {
        $bounds = Period::bounds($period);
        $counts = [];

        foreach (self::TRACKED_STEPS as $key => [$type, $label]) {
            $counts[$key] = [$label, $this->filtered(TrackingEvent::query(), $filters)
                ->where('type', $type)
                ->whereBetween('created_at', $bounds)
                ->distinct()
                ->count('session_id')];
        }

        $leads = $this->filtered(Lead::query(), $filters)->whereBetween('created_at', $bounds)->get(['id', 'status']);
        $reached = $this->furthestStage($leads);

        $counts['leads'] = ['Virou lead', $leads->count()];
        $counts['conversa'] = ['Conversa agendada', $reached->filter(fn (int $order) => $order >= LeadStatus::ConversaAgendada->order())->count()];
        $counts['proposta'] = ['Proposta', $reached->filter(fn (int $order) => $order >= LeadStatus::Proposta->order())->count()];
        $counts['cliente'] = ['Cliente', $leads->where('status', LeadStatus::Fechado)->count()];

        $previous = null;
        $steps = collect($counts)->map(function (array $step, string $key) use (&$previous) {
            [$label, $count] = $step;
            $rate = $previous === null ? null : ($previous > 0 ? $count / $previous : null);
            $previous = $count;

            return new FunnelStep($key, $label, $count, $rate);
        })->values();

        $bottleneck = $steps->filter(fn (FunnelStep $step) => $step->rateFromPrevious !== null)->sortBy('rateFromPrevious')->first();

        return $steps->map(fn (FunnelStep $step) => $step === $bottleneck ? $step->withBottleneck() : $step);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{channel?: ?string, campaign?: ?string}  $filters
     * @return Builder<TModel>
     */
    private function filtered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['channel'] ?? null, fn (Builder $query, string $channel) => $query->where('channel', $channel))
            ->when($filters['campaign'] ?? null, fn (Builder $query, string $campaign) => $query->where('utm_campaign', $campaign));
    }

    /**
     * Etapa mais avançada que cada lead alcançou (status atual ou histórico), ignorando Perdido.
     *
     * @param  Collection<int, Lead>  $leads
     * @return Collection<int, int> lead_id => ordem da etapa
     */
    private function furthestStage(Collection $leads): Collection
    {
        $fromHistory = LeadActivity::query()
            ->whereIn('lead_id', $leads->pluck('id'))
            ->where('type', ActivityType::StatusChanged)
            ->get(['lead_id', 'data'])
            ->groupBy('lead_id')
            ->map(fn (Collection $activities) => $activities
                ->map(fn (LeadActivity $activity) => LeadStatus::tryFrom($activity->data['to'] ?? ''))
                ->filter(fn (?LeadStatus $status) => $status !== null && $status !== LeadStatus::Perdido)
                ->map(fn (LeadStatus $status) => $status->order())
                ->max() ?? 0);

        return $leads->mapWithKeys(fn (Lead $lead) => [
            $lead->id => max(
                $lead->status === LeadStatus::Perdido ? 0 : $lead->status->order(),
                $fromHistory->get($lead->id, 0),
            ),
        ]);
    }
}

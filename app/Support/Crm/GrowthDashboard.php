<?php

namespace App\Support\Crm;

use App\Enums\Channel;
use App\Enums\LeadLevel;
use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Enums\Plan;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Sale;
use App\Support\Crm\Data\Kpi;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Orquestra os serviços de métricas; não recalcula nada que eles já calculam.
 */
class GrowthDashboard
{
    public function __construct(
        private RevenueMetrics $revenue,
        private AdMetrics $ads,
        private CashFlow $cash,
        private GoalProgress $goal,
        private FunnelMetrics $funnel,
    ) {}

    /**
     * @return array{goal: array<string, mixed>, kpis: array<int, Kpi>, actions: array<int, array{count: int, label: string, url: string}>}
     */
    public function overview(CarbonPeriod $period): array
    {
        $previous = Period::previous($period);
        $end = CarbonImmutable::instance($period->getEndDate())->min(Period::today());
        $previousEnd = CarbonImmutable::instance($previous->getEndDate());

        $ads = $this->ads->summary($period);
        $previousAds = $this->ads->summary($previous);

        return [
            'goal' => $this->goal->for(Period::today(), Period::today()),
            'kpis' => [
                new Kpi('Faturado', $this->revenue->revenue($period), $this->revenue->revenue($previous), 'money', 'Pagamentos recebidos no período'),
                new Kpi('MRR', $this->revenue->mrrAt($end), $this->revenue->mrrAt($previousEnd), 'money', 'Mensalidades ativas'),
                new Kpi('Alunos ativos', $this->revenue->activeSubscriptionsAt($end), $this->revenue->activeSubscriptionsAt($previousEnd)),
                new Kpi('Leads', $this->leadsCount($period), $this->leadsCount($previous)),
                new Kpi('Conversão lead → cliente', $this->conversion($period), $this->conversion($previous), 'percent', 'Clientes novos ÷ leads do período'),
                new Kpi('Investido no Meta', $ads['spend'], $previousAds['spend'], 'money', lowerIsBetter: true),
                new Kpi('ROAS', $ads['roas'], $previousAds['roas'], 'multiplier', 'Receita do Meta ÷ investido'),
                new Kpi('Saldo em conta', $this->cash->balance(), null, 'money', 'Hoje'),
            ],
            'actions' => [
                ['count' => Lead::overdue()->count(), 'label' => 'follow-ups atrasados', 'url' => route('crm.leads.index', ['flag' => 'atrasados'])],
                ['count' => Lead::cooling()->count(), 'label' => 'leads esfriando (24h+ sem contato)', 'url' => route('crm.leads.index', ['flag' => 'esfriando'])],
                ['count' => Payment::where('status', PaymentStatus::Pending)->where('due_date', '<', Period::today()->toDateString())->count(), 'label' => 'cobranças atrasadas', 'url' => route('crm.sales.index', ['aba' => 'cobrancas'])],
                ['count' => Lead::where('status', LeadStatus::ConversaAgendada)->whereBetween('meeting_at', [Period::today()->startOfDay()->utc(), Period::today()->endOfDay()->utc()])->count(), 'label' => 'conversas agendadas hoje', 'url' => route('crm.leads.index', ['status' => LeadStatus::ConversaAgendada->value])],
            ],
        ];
    }

    /**
     * @param  array{channel?: ?string, campaign?: ?string}  $filters
     * @return Collection<int, Data\FunnelStep>
     */
    public function funnel(CarbonPeriod $period, array $filters = []): Collection
    {
        return $this->funnel->steps($period, $filters);
    }

    /**
     * @return array{channels: Collection<int, array<string, mixed>>, campaigns: Collection<int, array<string, mixed>>, levels: Collection<int, array<string, mixed>>, plans: Collection<int, array<string, mixed>>, topics: array<string, int>}
     */
    public function acquisition(CarbonPeriod $period): array
    {
        $leads = Lead::whereBetween('created_at', Period::bounds($period))->get(['id', 'channel', 'level', 'plan_interest', 'status', 'goal']);
        $revenueByChannel = $this->revenue->revenueByChannel($period);

        $segment = fn (Collection $group, string $label, int $revenue = 0) => [
            'label' => $label,
            'leads' => $group->count(),
            'customers' => $closed = $group->where('status', LeadStatus::Fechado)->count(),
            'conversion' => $group->isEmpty() ? null : $closed / $group->count(),
            'revenue' => $revenue,
        ];

        return [
            'channels' => collect(Channel::cases())
                ->map(fn (Channel $channel) => $segment($leads->where('channel', $channel), $channel->label(), $revenueByChannel[$channel->value] ?? 0))
                ->filter(fn (array $row) => $row['leads'] > 0 || $row['revenue'] > 0)
                ->sortByDesc('leads')->values(),
            'campaigns' => $this->ads->byCampaign($period),
            'levels' => collect(LeadLevel::cases())
                ->map(fn (LeadLevel $level) => $segment($leads->where('level', $level), $level->label()))
                ->sortByDesc('leads')->values(),
            'plans' => collect([...Plan::cases(), null])
                ->map(fn (?Plan $plan) => $segment($leads->where('plan_interest', $plan), $plan?->label() ?? 'Ainda não sabe'))
                ->sortByDesc('leads')->values(),
            'topics' => TopicExtractor::top($leads->pluck('goal')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function revenue(): array
    {
        $today = Period::today();
        $last90 = Period::preset('90d');

        return [
            'monthly' => $this->revenue->monthlyRevenue(12),
            'goal' => GoalProgress::goalCents(),
            'mrr' => $this->revenue->mrr(),
            'active' => $this->revenue->activeSubscriptions(),
            'churn' => $this->revenue->churnRate($today),
            'previous_churn' => $this->revenue->churnRate($today->subMonthNoOverflow()),
            'ltv' => $this->revenue->ltvCents(),
            'lifetime' => $this->revenue->lifetimeMonths(),
            'lifetime_measured' => $this->revenue->averageLifetimeMonths() !== null,
            'tickets' => $this->revenue->ticketByPlan($last90),
            'upsell' => $this->revenue->upsellCount($last90),
            'sessions_sold' => Sale::where('plan', Plan::Sessao)->whereBetween('sold_at', Period::bounds($last90))->count(),
        ];
    }

    private function leadsCount(CarbonPeriod $period): int
    {
        return Lead::whereBetween('created_at', Period::bounds($period))->count();
    }

    private function conversion(CarbonPeriod $period): ?float
    {
        $leads = $this->leadsCount($period);

        return $leads === 0 ? null : $this->revenue->newCustomers($period) / $leads;
    }
}

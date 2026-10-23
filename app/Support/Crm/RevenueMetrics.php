<?php

namespace App\Support\Crm;

use App\Enums\PaymentStatus;
use App\Enums\Plan;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;

/**
 * Faturamento = pagamentos recebidos (regime de caixa, DP-0501).
 */
class RevenueMetrics
{
    /**
     * @return Builder<Payment>
     */
    private function paidIn(CarbonPeriod $period): Builder
    {
        return Payment::query()
            ->where('payments.status', PaymentStatus::Paid)
            ->whereBetween('payments.paid_at', Period::bounds($period));
    }

    public function revenue(CarbonPeriod $period): int
    {
        return (int) $this->paidIn($period)->sum('payments.amount_cents');
    }

    public function revenueForMonth(CarbonImmutable $month): int
    {
        return $this->revenue(Period::month($month));
    }

    /**
     * @return array<string, int> canal => centavos
     */
    public function revenueByChannel(CarbonPeriod $period): array
    {
        return $this->paidIn($period)
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->groupBy('sales.channel')
            ->selectRaw('sales.channel as channel, sum(payments.amount_cents) as total')
            ->pluck('total', 'channel')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @return array<string, int> campanha normalizada => centavos
     */
    public function revenueByCampaign(CarbonPeriod $period): array
    {
        return $this->paidIn($period)
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->whereNotNull('sales.utm_campaign')
            ->get(['sales.utm_campaign', 'payments.amount_cents'])
            ->groupBy(fn ($row) => UtmKey::normalize($row->utm_campaign))
            ->map(fn ($rows) => (int) $rows->sum('amount_cents'))
            ->all();
    }

    /**
     * Faturamento dos últimos N meses, do mais antigo ao atual.
     *
     * @return array<string, int> 'Y-m' => centavos
     */
    public function monthlyRevenue(int $months = 12): array
    {
        $current = Period::today()->startOfMonth();

        return collect(range($months - 1, 0))
            ->mapWithKeys(fn (int $back) => [
                $current->subMonthsNoOverflow($back)->format('Y-m') => $this->revenueForMonth($current->subMonthsNoOverflow($back)),
            ])
            ->all();
    }

    public function mrr(): int
    {
        return (int) Subscription::where('status', SubscriptionStatus::Active)->sum('monthly_amount_cents');
    }

    public function activeSubscriptions(): int
    {
        return Subscription::where('status', SubscriptionStatus::Active)->count();
    }

    /**
     * Canceladas no mês ÷ ativas no início do mês; null sem base.
     */
    public function churnRate(CarbonImmutable $month): ?float
    {
        [$start, $end] = Period::bounds(Period::month($month));

        $activeAtStart = Subscription::query()
            ->where('started_at', '<', $start)
            ->where(fn (Builder $query) => $query->whereNull('canceled_at')->orWhere('canceled_at', '>=', $start))
            ->count();

        if ($activeAtStart === 0) {
            return null;
        }

        $canceled = Subscription::query()
            ->where('started_at', '<', $start)
            ->whereBetween('canceled_at', [$start, $end])
            ->count();

        return $canceled / $activeAtStart;
    }

    /**
     * Média de meses das assinaturas encerradas; null com menos de 3 para não inventar.
     */
    public function averageLifetimeMonths(): ?float
    {
        $ended = Subscription::where('status', SubscriptionStatus::Canceled)->whereNotNull('canceled_at')->get(['started_at', 'canceled_at']);

        if ($ended->count() < 3) {
            return null;
        }

        return round($ended->avg(fn (Subscription $subscription) => max(1, $subscription->started_at->diffInDays($subscription->canceled_at) / 30)), 1);
    }

    public function lifetimeMonths(): float
    {
        return $this->averageLifetimeMonths() ?? (float) config('iris.crm.default_lifetime_months');
    }

    /**
     * LTV = mensalidade média × tempo médio de permanência.
     */
    public function ltvCents(): int
    {
        $averageMonthly = Subscription::avg('monthly_amount_cents') ?? Plan::Mentoria->priceCents();

        return (int) round($averageMonthly * $this->lifetimeMonths());
    }

    /**
     * Leads que compraram sessão avulsa e depois viraram mentoria.
     */
    public function upsellCount(CarbonPeriod $period): int
    {
        [$start, $end] = Period::bounds($period);

        return Sale::query()
            ->from('sales as mentoria')
            ->where('mentoria.plan', Plan::Mentoria)
            ->whereBetween('mentoria.sold_at', [$start, $end])
            ->whereExists(fn ($query) => $query->from('sales as sessao')
                ->whereColumn('sessao.lead_id', 'mentoria.lead_id')
                ->where('sessao.plan', Plan::Sessao)
                ->whereColumn('sessao.sold_at', '<', 'mentoria.sold_at'))
            ->distinct()
            ->count('mentoria.lead_id');
    }

    /**
     * Leads cuja primeira venda caiu no período.
     */
    public function newCustomers(CarbonPeriod $period): int
    {
        [$start, $end] = Period::bounds($period);

        return Sale::query()
            ->selectRaw('lead_id, min(sold_at) as first_sale')
            ->groupBy('lead_id')
            ->get()
            ->filter(fn ($row) => CarbonImmutable::parse($row->first_sale)->betweenIncluded($start, $end))
            ->count();
    }

    /**
     * @return array<string, array{count: int, average: ?int}> plano => vendas e ticket médio
     */
    public function ticketByPlan(CarbonPeriod $period): array
    {
        $sales = Sale::whereBetween('sold_at', Period::bounds($period))->get(['plan', 'amount_cents']);

        return collect(Plan::cases())->mapWithKeys(function (Plan $plan) use ($sales) {
            $ofPlan = $sales->where('plan', $plan);

            return [$plan->value => [
                'count' => $ofPlan->count(),
                'average' => $ofPlan->isEmpty() ? null : (int) round($ofPlan->avg('amount_cents')),
            ]];
        })->all();
    }
}

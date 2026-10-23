<?php

namespace App\Support\Crm;

use App\Enums\Channel;
use App\Enums\PaymentStatus;
use App\Models\AdSpend;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Cruza o gasto do Meta com leads, clientes e receita. Sem gasto, as razões ficam null ("—").
 */
class AdMetrics
{
    public function __construct(private RevenueMetrics $revenue) {}

    /**
     * @return array{spend: ?int, impressions: int, clicks: int, leads: int, customers: int, revenue: int, cpl: ?int, cac: ?int, roas: ?float, ltv_roas: ?float, is_unprofitable: bool}
     */
    public function summary(CarbonPeriod $period): array
    {
        $spends = $this->spends($period);

        return $this->ratios(
            spend: $spends->isEmpty() ? null : (int) $spends->sum('spend_cents'),
            leads: $this->metaLeads($period)->count(),
            customers: $this->newMetaCustomers($period)->count(),
            revenue: $this->metaRevenue($period)->sum('amount_cents'),
            impressions: (int) $spends->sum('impressions'),
            clicks: (int) $spends->sum('link_clicks'),
        );
    }

    /**
     * Uma linha por campanha (com gasto ou com lead do Meta), ordenada por investimento.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byCampaign(CarbonPeriod $period): Collection
    {
        return $this->breakdown($period, 'campaign_key', 'campaign', 'utm_campaign');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function byAdset(CarbonPeriod $period, string $campaignKey): Collection
    {
        return $this->breakdown($period, 'adset_key', 'adset', 'utm_content', $campaignKey);
    }

    /**
     * @return array<string, int> 'Y-m-d' => centavos, todos os dias do período
     */
    public function dailySpend(CarbonPeriod $period): array
    {
        $byDay = $this->spends($period)->groupBy('date')->map(fn ($rows) => (int) $rows->sum('spend_cents'));

        return collect(CarbonPeriod::create($period->getStartDate(), '1 day', $period->getEndDate()))
            ->mapWithKeys(fn ($day) => [$day->toDateString() => $byDay->get($day->toDateString(), 0)])
            ->all();
    }

    public function leadsOutsideMeta(CarbonPeriod $period): int
    {
        return Lead::whereBetween('created_at', Period::bounds($period))->where('channel', '!=', Channel::Meta)->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function breakdown(CarbonPeriod $period, string $spendKey, string $spendLabel, string $utmField, ?string $campaignKey = null): Collection
    {
        $spends = $this->spends($period)
            ->when($campaignKey !== null, fn (Collection $rows) => $rows->where('campaign_key', $campaignKey))
            ->groupBy($spendKey);

        $leads = $this->metaLeads($period)
            ->when($campaignKey !== null, fn (Collection $rows) => $rows->filter(fn (Lead $lead) => UtmKey::normalize($lead->utm_campaign) === $campaignKey))
            ->groupBy(fn (Lead $lead) => UtmKey::normalize($lead->{$utmField}) ?? '');

        $customers = $this->newMetaCustomers($period)
            ->when($campaignKey !== null, fn (Collection $rows) => $rows->filter(fn (Sale $sale) => UtmKey::normalize($sale->utm_campaign) === $campaignKey))
            ->groupBy(fn (Sale $sale) => UtmKey::normalize($sale->{$utmField}) ?? '');

        $revenue = $this->metaRevenue($period)
            ->when($campaignKey !== null, fn (Collection $rows) => $rows->filter(fn ($payment) => UtmKey::normalize($payment->utm_campaign) === $campaignKey))
            ->groupBy(fn ($payment) => UtmKey::normalize($payment->{$utmField}) ?? '');

        return $spends->keys()->merge($leads->keys())->unique()
            ->map(function (string $key) use ($spends, $leads, $customers, $revenue, $spendLabel, $utmField) {
                $spendRows = $spends->get($key, collect());

                return [
                    'key' => $key,
                    'name' => $spendRows->first()?->{$spendLabel} ?? $leads->get($key)?->first()?->{$utmField} ?? 'Sem identificação',
                    ...$this->ratios(
                        spend: $spendRows->isEmpty() ? null : (int) $spendRows->sum('spend_cents'),
                        leads: $leads->get($key, collect())->count(),
                        customers: $customers->get($key, collect())->count(),
                        revenue: (int) $revenue->get($key, collect())->sum('amount_cents'),
                        impressions: (int) $spendRows->sum('impressions'),
                        clicks: (int) $spendRows->sum('link_clicks'),
                    ),
                ];
            })
            ->sortByDesc(fn (array $row) => $row['spend'] ?? -1)
            ->values();
    }

    /**
     * @return array{spend: ?int, impressions: int, clicks: int, leads: int, customers: int, revenue: int, cpl: ?int, cac: ?int, roas: ?float, ltv_roas: ?float, is_unprofitable: bool}
     */
    private function ratios(?int $spend, int $leads, int $customers, int $revenue, int $impressions, int $clicks): array
    {
        $cac = $spend !== null && $customers > 0 ? intdiv($spend, $customers) : null;
        $ltv = $this->revenue->ltvCents();
        $cpl = $spend !== null && $leads > 0 ? intdiv($spend, $leads) : null;

        return [
            'spend' => $spend,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'leads' => $leads,
            'customers' => $customers,
            'revenue' => $revenue,
            'cpl' => $cpl,
            'cac' => $cac,
            'roas' => $spend ? round($revenue / $spend, 2) : null,
            'ltv_roas' => $spend ? round($customers * $ltv / $spend, 2) : null,
            'is_unprofitable' => $this->isUnprofitable($spend, $cpl, $cac, $customers, $ltv),
        ];
    }

    /**
     * CAC acima do LTV. Sem cliente ainda, estima o CAC pelo CPL ÷ conversão histórica lead → cliente.
     */
    private function isUnprofitable(?int $spend, ?int $cpl, ?int $cac, int $customers, int $ltv): bool
    {
        if ($cac !== null) {
            return $cac > $ltv;
        }

        if ($spend === null || $customers > 0) {
            return false;
        }

        if ($cpl === null) {
            return $spend > $ltv;
        }

        $conversion = $this->historicalConversion();

        return $conversion !== null && $cpl / $conversion > $ltv;
    }

    /**
     * Conversão lead → cliente de toda a base; null com menos de 20 leads (pouca amostra).
     */
    private function historicalConversion(): ?float
    {
        return $this->historicalConversion ??= (function () {
            $leads = Lead::count();

            return $leads < 20 ? null : max(Sale::distinct()->count('lead_id'), 1) / $leads;
        })();
    }

    private ?float $historicalConversion = null;

    /**
     * @return Collection<int, AdSpend>
     */
    private function spends(CarbonPeriod $period): Collection
    {
        return AdSpend::query()
            ->where('platform', 'meta')
            ->whereBetween('date', [
                CarbonImmutable::instance($period->getStartDate())->toDateString(),
                CarbonImmutable::instance($period->getEndDate())->toDateString(),
            ])
            ->get();
    }

    /**
     * @return Collection<int, Lead>
     */
    private function metaLeads(CarbonPeriod $period): Collection
    {
        return Lead::whereBetween('created_at', Period::bounds($period))
            ->where('channel', Channel::Meta)
            ->get(['id', 'utm_campaign', 'utm_content', 'utm_term']);
    }

    /**
     * Primeira venda de cada lead do Meta, quando ela caiu no período.
     *
     * @return Collection<int, Sale>
     */
    private function newMetaCustomers(CarbonPeriod $period): Collection
    {
        [$start, $end] = Period::bounds($period);

        return Sale::where('channel', Channel::Meta)
            ->orderBy('sold_at')
            ->get(['lead_id', 'sold_at', 'utm_campaign', 'utm_content', 'utm_term'])
            ->unique('lead_id')
            ->filter(fn (Sale $sale) => $sale->sold_at->betweenIncluded($start, $end))
            ->values();
    }

    /**
     * @return Collection<int, Payment>
     */
    private function metaRevenue(CarbonPeriod $period): Collection
    {
        return Payment::query()
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.channel', Channel::Meta)
            ->where('payments.status', PaymentStatus::Paid)
            ->whereBetween('payments.paid_at', Period::bounds($period))
            ->get(['payments.amount_cents', 'sales.utm_campaign', 'sales.utm_content', 'sales.utm_term']);
    }
}

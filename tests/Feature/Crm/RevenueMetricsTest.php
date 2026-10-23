<?php

namespace Tests\Feature\Crm;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\Subscription;
use App\Support\Crm\RevenueMetrics;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_revenue_uses_sao_paulo_month_boundaries(): void
    {
        Payment::factory()->paid(CarbonImmutable::parse('2026-10-31 23:30', 'America/Sao_Paulo')->utc())->create(['amount_cents' => 10000]);
        Payment::factory()->paid(CarbonImmutable::parse('2026-11-01 00:30', 'America/Sao_Paulo')->utc())->create(['amount_cents' => 20000]);
        Payment::factory()->create(['amount_cents' => 99900]);

        $metrics = new RevenueMetrics;

        $this->assertSame(10000, $metrics->revenueForMonth(CarbonImmutable::parse('2026-10-15', 'America/Sao_Paulo')));
        $this->assertSame(20000, $metrics->revenueForMonth(CarbonImmutable::parse('2026-11-15', 'America/Sao_Paulo')));
    }

    public function test_mrr_counts_only_active_subscriptions(): void
    {
        Subscription::factory()->count(2)->create();
        Subscription::factory()->canceled()->create();

        $this->assertSame(79980, (new RevenueMetrics)->mrr());
    }

    public function test_churn_rate_is_null_without_a_base(): void
    {
        $this->assertNull((new RevenueMetrics)->churnRate(CarbonImmutable::parse('2026-10-01')));
    }

    public function test_churn_rate_divides_cancellations_by_active_at_month_start(): void
    {
        Subscription::factory()->count(3)->create(['started_at' => '2026-08-10']);
        Subscription::factory()->canceled(CarbonImmutable::parse('2026-10-12'))->create(['started_at' => '2026-08-10']);

        $this->assertEqualsWithDelta(0.25, (new RevenueMetrics)->churnRate(CarbonImmutable::parse('2026-10-15', 'America/Sao_Paulo')), 0.001);
    }

    public function test_ltv_falls_back_to_the_configured_lifetime(): void
    {
        Subscription::factory()->create();

        $this->assertSame(39990 * 3, (new RevenueMetrics)->ltvCents());
    }

    public function test_upsell_counts_sessions_that_became_mentorships(): void
    {
        $session = Sale::factory()->create(['sold_at' => '2026-09-01']);
        Sale::factory()->mentoria()->create(['lead_id' => $session->lead_id, 'sold_at' => '2026-10-05']);
        Sale::factory()->mentoria()->create(['sold_at' => '2026-10-06']);

        $period = CarbonPeriod::create('2026-10-01', '2026-10-31 23:59:59');

        $this->assertSame(1, (new RevenueMetrics)->upsellCount($period));
    }
}

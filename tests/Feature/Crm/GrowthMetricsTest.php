<?php

namespace Tests\Feature\Crm;

use App\Enums\ActivityType;
use App\Enums\EventType;
use App\Enums\GoalStatus;
use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\TrackingEvent;
use App\Support\Crm\FunnelMetrics;
use App\Support\Crm\GoalProgress;
use App\Support\Crm\Period;
use App\Support\Crm\TopicExtractor;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GrowthMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_goal_progress_halfway_through_the_month(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-16 12:00', 'America/Sao_Paulo'));
        Payment::factory()->paid(CarbonImmutable::parse('2026-10-05 10:00', 'America/Sao_Paulo')->utc())->create(['amount_cents' => 200000]);

        $progress = app(GoalProgress::class)->for(Period::today(), Period::today());

        $this->assertSame(400000, $progress['goal']);
        $this->assertSame(0.5, $progress['percent']);
        $this->assertSame(200000, $progress['remaining']);
        $this->assertSame(16, $progress['days_left']);
        $this->assertSame(12500, $progress['daily_needed']);
    }

    public function test_pending_charges_count_towards_the_projection(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-10 12:00', 'America/Sao_Paulo'));
        $subscription = Subscription::factory()->create();
        Payment::factory()->paid(now()->subDay())->create(['sale_id' => $subscription->sale_id, 'subscription_id' => $subscription->id, 'amount_cents' => 300000, 'due_date' => '2026-10-09']);
        Payment::factory()->create(['sale_id' => $subscription->sale_id, 'subscription_id' => $subscription->id, 'amount_cents' => 100000, 'due_date' => '2026-10-25']);

        $progress = app(GoalProgress::class)->for(Period::today(), Period::today());

        $this->assertSame(400000, $progress['projection']);
        $this->assertSame(GoalStatus::OnTrack, $progress['status']);
    }

    public function test_reaching_the_goal_is_flagged(): void
    {
        Payment::factory()->paid()->create(['amount_cents' => 450000]);

        $this->assertSame(GoalStatus::Reached, app(GoalProgress::class)->for(Period::today(), Period::today())['status']);
    }

    public function test_funnel_counts_sessions_then_leads_and_flags_the_bottleneck(): void
    {
        foreach (range(1, 10) as $visit) {
            $session = (string) Str::uuid();
            TrackingEvent::factory()->create(['session_id' => $session]);
            TrackingEvent::factory()->create(['session_id' => $session]);

            if ($visit <= 5) {
                TrackingEvent::factory()->type(EventType::CtaClick, 'hero')->create(['session_id' => $session]);
                TrackingEvent::factory()->type(EventType::FormView)->create(['session_id' => $session]);
                TrackingEvent::factory()->type(EventType::FormStart)->create(['session_id' => $session]);
            }
        }

        $leads = Lead::factory()->count(4)->create();
        $lost = $leads[0];
        $lost->update(['status' => LeadStatus::Perdido, 'lost_reason' => LostReason::Preco]);
        $lost->activities()->create(['type' => ActivityType::StatusChanged, 'data' => ['from' => 'conversa_agendada', 'to' => 'proposta'], 'created_at' => now()]);
        $leads[1]->update(['status' => LeadStatus::Fechado]);

        $steps = app(FunnelMetrics::class)->steps(Period::preset('mes'))->keyBy('key');

        $this->assertSame(10, $steps['visitas']->count);
        $this->assertSame(5, $steps['cta']->count);
        $this->assertSame(0.5, $steps['cta']->rateFromPrevious);
        $this->assertSame(4, $steps['leads']->count);
        $this->assertSame(2, $steps['proposta']->count);
        $this->assertSame(1, $steps['cliente']->count);
        $this->assertTrue($steps['cta']->isBottleneck);
        $this->assertSame(1, $steps->where('isBottleneck', true)->count());
    }

    public function test_topic_extractor_ignores_stopwords_and_accents(): void
    {
        $topics = TopicExtractor::top(collect(['Quero usar agentes no TCC', 'Agentes e automação', null, 'automação de testes']));

        $this->assertSame(['agentes' => 2, 'automacao' => 2, 'testes' => 1], $topics);
    }
}

<?php

namespace Tests\Feature\Crm;

use App\Enums\EventType;
use App\Models\AdSpend;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Support\Crm\GoalProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create();

        $subscription = Subscription::factory()->create();
        Payment::factory()->paid()->create(['sale_id' => $subscription->sale_id, 'subscription_id' => $subscription->id, 'amount_cents' => 39990]);
        Lead::factory()->fromMeta()->create(['goal' => 'Quero usar agentes no meu TCC']);
        TrackingEvent::factory()->type(EventType::PageView)->create();
        AdSpend::factory()->create();
    }

    public function test_every_tab_renders(): void
    {
        $this->actingAs($this->admin)->get(route('crm.dashboard'))->assertOk()->assertSee('Meta de')->assertSee('Ações de hoje');

        foreach (['funil' => 'Da visita ao cliente', 'aquisicao' => 'Por canal', 'receita' => 'Faturamento dos últimos 12 meses'] as $tab => $text) {
            $this->actingAs($this->admin)->get(route('crm.dashboard.tab', $tab))->assertOk()->assertSee($text);
        }
    }

    public function test_unknown_tab_falls_back_to_the_overview(): void
    {
        $this->actingAs($this->admin)->get(route('crm.dashboard.tab', 'xpto'))->assertRedirect(route('crm.dashboard'));
    }

    public function test_custom_period_is_accepted(): void
    {
        $this->actingAs($this->admin)
            ->get(route('crm.dashboard', ['periodo' => 'custom', 'de' => '2026-09-01', 'ate' => '2026-09-30']))
            ->assertOk()
            ->assertSee('01/09/2026 – 30/09/2026');
    }

    public function test_admin_updates_the_monthly_goal(): void
    {
        $this->actingAs($this->admin)->put(route('crm.settings.goal'), ['goal' => '5.000,00'])->assertSessionHas('status');

        $this->assertSame(500000, GoalProgress::goalCents());
        $this->assertSame(500000, Setting::get(GoalProgress::GOAL_KEY));
    }

    public function test_invalid_goal_is_rejected(): void
    {
        $this->actingAs($this->admin)->put(route('crm.settings.goal'), ['goal' => 'muito'])->assertSessionHasErrors('goal');
    }
}

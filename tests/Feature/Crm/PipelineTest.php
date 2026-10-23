<?php

namespace Tests\Feature\Crm;

use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create();
    }

    public function test_board_shows_leads_by_stage(): void
    {
        Lead::factory()->status(LeadStatus::Proposta)->create(['name' => 'Na proposta']);
        Lead::factory()->status(LeadStatus::Perdido)->create(['name' => 'Perdido antigo', 'status_changed_at' => now()->subDays(40)]);

        $this->actingAs($this->admin)
            ->get(route('crm.pipeline'))
            ->assertOk()
            ->assertSeeInOrder(['Novo', 'Contato feito', 'Proposta', 'Fechado', 'Perdido'])
            ->assertSee('Na proposta')
            ->assertDontSee('Perdido antigo');
    }

    public function test_moving_a_lead_records_the_change(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin)
            ->patchJson(route('crm.leads.status', $lead), ['status' => 'contato_feito'])
            ->assertOk()
            ->assertJson(['status' => 'contato_feito']);

        $activity = $lead->activities()->sole();
        $this->assertSame(['from' => 'novo', 'to' => 'contato_feito'], $activity->data);
        $this->assertSame($this->admin->id, $activity->user_id);
    }

    public function test_losing_a_lead_requires_a_reason(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin)
            ->patchJson(route('crm.leads.status', $lead), ['status' => 'perdido'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lost_reason');

        $this->assertSame(LeadStatus::Novo, $lead->fresh()->status);
    }

    public function test_closing_requires_a_sale(): void
    {
        $lead = Lead::factory()->status(LeadStatus::Proposta)->create();

        $this->actingAs($this->admin)
            ->patchJson(route('crm.leads.status', $lead), ['status' => 'fechado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_reopening_a_lost_lead_clears_the_reason(): void
    {
        $lead = Lead::factory()->status(LeadStatus::Perdido)->create(['lost_reason' => LostReason::Preco]);

        $this->actingAs($this->admin)
            ->patchJson(route('crm.leads.status', $lead), ['status' => 'contato_feito'])
            ->assertOk();

        $this->assertNull($lead->fresh()->lost_reason);
    }

    public function test_scheduling_a_meeting_stores_its_date(): void
    {
        $lead = Lead::factory()->status(LeadStatus::ContatoFeito)->create();

        $this->actingAs($this->admin)
            ->patchJson(route('crm.leads.status', $lead), ['status' => 'conversa_agendada', 'meeting_at' => '2026-11-03 15:00:00'])
            ->assertOk();

        $this->assertNotNull($lead->fresh()->meeting_at);
    }
}

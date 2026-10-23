<?php

namespace Tests\Feature\Crm;

use App\Enums\ActivityType;
use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create();
    }

    public function test_guests_cannot_see_leads(): void
    {
        $this->get(route('crm.leads.index'))->assertRedirect(route('crm.login'));
    }

    public function test_list_filters_and_summarizes(): void
    {
        Lead::factory()->fromMeta('vibe-coding-junior')->create(['name' => 'Ana Meta']);
        Lead::factory()->status(LeadStatus::Perdido)->create(['name' => 'Bruno Direto']);

        $this->actingAs($this->admin)
            ->get(route('crm.leads.index', ['channel' => 'meta']))
            ->assertOk()
            ->assertSee('Ana Meta')
            ->assertDontSee('Bruno Direto')
            ->assertSee('vibe-coding-junior');
    }

    public function test_search_matches_whatsapp_digits(): void
    {
        Lead::factory()->create(['name' => 'Carla', 'whatsapp' => '11987654321']);
        Lead::factory()->create(['name' => 'Diego', 'whatsapp' => '21911112222']);

        $this->actingAs($this->admin)
            ->get(route('crm.leads.index', ['q' => '98765-4321']))
            ->assertSee('Carla')
            ->assertDontSee('Diego');
    }

    public function test_overdue_flag_lists_only_late_follow_ups(): void
    {
        Lead::factory()->status(LeadStatus::ContatoFeito)->create(['name' => 'Atrasada', 'next_action' => 'Ligar', 'next_action_at' => now()->subDay()]);
        Lead::factory()->status(LeadStatus::ContatoFeito)->create(['name' => 'Em dia', 'next_action' => 'Ligar', 'next_action_at' => now()->addDay()]);

        $this->actingAs($this->admin)
            ->get(route('crm.leads.index', ['flag' => 'atrasados']))
            ->assertSee('Atrasada')
            ->assertDontSee('Em dia');
    }

    public function test_admin_registers_a_manual_lead(): void
    {
        $this->actingAs($this->admin)->post(route('crm.leads.store'), [
            'name' => 'Indicado',
            'email' => 'INDICADO@example.com',
            'level' => 'estudante',
            'channel' => 'indicacao',
        ])->assertRedirect();

        $lead = Lead::sole();
        $this->assertSame('indicado@example.com', $lead->email);
        $this->assertSame(Channel::Indicacao, $lead->channel);
        $this->assertSame(ActivityType::Created, $lead->activities()->sole()->type);
    }

    public function test_dossier_shows_the_timeline(): void
    {
        $lead = Lead::factory()->fromMeta()->create();
        TrackingEvent::factory()->create(['visitor_id' => $lead->visitor_id, 'target' => 'hero', 'type' => 'cta_click']);
        $this->actingAs($this->admin)->post(route('crm.leads.notes', $lead), ['text' => 'Pediu proposta por e-mail']);

        $this->actingAs($this->admin)
            ->get(route('crm.leads.show', $lead))
            ->assertOk()
            ->assertSee('Pediu proposta por e-mail')
            ->assertSee('Clicou em um CTA (hero)');
    }

    public function test_next_action_is_saved_in_sao_paulo_time(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin)->patch(route('crm.leads.next-action', $lead), [
            'next_action' => 'Enviar proposta',
            'next_action_at' => '2026-11-02T10:00',
        ])->assertRedirect();

        $this->assertSame('2026-11-02 13:00:00', $lead->fresh()->next_action_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_export_respects_filters(): void
    {
        Lead::factory()->fromMeta()->create(['name' => 'Exportada']);
        Lead::factory()->create(['name' => 'Fora do filtro']);

        $response = $this->actingAs($this->admin)->get(route('crm.leads.export', ['channel' => 'meta']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Exportada', $csv);
        $this->assertStringNotContainsString('Fora do filtro', $csv);
    }

    public function test_deleting_a_lead_removes_its_tracked_events(): void
    {
        $lead = Lead::factory()->create();
        TrackingEvent::factory()->create(['visitor_id' => $lead->visitor_id]);
        $this->actingAs($this->admin)->post(route('crm.leads.notes', $lead), ['text' => 'nota']);

        $this->actingAs($this->admin)->delete(route('crm.leads.destroy', $lead))->assertRedirect(route('crm.leads.index'));

        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('lead_activities', 0);
        $this->assertDatabaseCount('tracking_events', 0);
    }
}

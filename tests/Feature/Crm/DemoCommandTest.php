<?php

namespace Tests\Feature\Crm;

use App\Enums\LeadStatus;
use App\Models\AdSpend;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\TrackingEvent;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('crm:demo')->assertFailed();

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_it_refuses_to_mix_with_existing_leads_without_fresh(): void
    {
        Lead::factory()->create();

        $this->artisan('crm:demo')->assertFailed();

        $this->assertSame(1, Lead::count());
    }

    public function test_it_builds_a_coherent_scenario_that_every_screen_can_render(): void
    {
        $this->withoutVite();

        $this->artisan('crm:demo', ['--scale' => '0.4'])->assertSuccessful();

        $sessions = TrackingEvent::distinct()->count('session_id');
        $leads = Lead::count();
        $customers = Sale::distinct()->count('lead_id');

        $this->assertGreaterThan($leads, $sessions);
        $this->assertGreaterThan($customers, $leads);
        $this->assertGreaterThan(0, $customers);
        $this->assertGreaterThan(0, AdSpend::count());
        $this->assertGreaterThan(0, Lead::cooling()->count());
        $this->assertSame(0, Lead::where('email', 'not like', '%@example.com')->count());
        $this->assertTrue(Lead::where('status', LeadStatus::Fechado)->exists());

        $admin = User::firstWhere('email', DemoSeeder::ADMIN_EMAIL);
        $pages = [
            route('crm.dashboard'),
            route('crm.dashboard.tab', 'funil'),
            route('crm.dashboard.tab', 'aquisicao'),
            route('crm.dashboard.tab', 'receita'),
            route('crm.pipeline'),
            route('crm.leads.index'),
            route('crm.leads.show', Lead::has('sales')->first()),
            route('crm.sales.index', ['aba' => 'assinaturas']),
            route('crm.sales.index', ['aba' => 'cobrancas']),
            route('crm.ads', ['periodo' => '90d']),
            route('crm.cash'),
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }
}

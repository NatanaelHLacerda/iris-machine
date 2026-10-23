<?php

namespace Tests\Feature;

use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreLeadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Ana Souza',
            'email' => 'Ana@Example.com',
            'whatsapp' => '(11) 98765-4321',
            'level' => 'junior',
            'plan' => 'mentoria',
            'goal' => 'Usar agentes no trabalho',
            ...$overrides,
        ];
    }

    public function test_it_stores_the_lead_with_its_first_submission(): void
    {
        $this->postJson(route('leads.store'), $this->payload([
            'utm_source' => 'facebook',
            'utm_campaign' => 'vibe-coding-junior',
            'landing_path' => '/',
        ]))->assertCreated()->assertJsonStructure(['message']);

        $lead = Lead::sole();
        $this->assertSame('ana@example.com', $lead->email);
        $this->assertSame('11987654321', $lead->whatsapp);
        $this->assertSame(LeadStatus::Novo, $lead->status);
        $this->assertSame(Channel::Meta, $lead->channel);
        $this->assertSame('vibe-coding-junior', $lead->utm_campaign);
        $this->assertCount(1, $lead->submissions);
        $this->assertNotNull($lead->submissions->first()->ip_hash);
        $this->assertNotSame('127.0.0.1', $lead->submissions->first()->ip_hash);
    }

    public function test_fbclid_alone_classifies_the_lead_as_meta(): void
    {
        $this->postJson(route('leads.store'), $this->payload(['fbclid' => 'abc123']))->assertCreated();

        $this->assertSame(Channel::Meta, Lead::sole()->channel);
    }

    public function test_it_returns_errors_per_field(): void
    {
        $this->postJson(route('leads.store'), ['email' => 'not-an-email', 'level' => 'ceo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'level']);

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_honeypot_fakes_success_without_storing(): void
    {
        $this->postJson(route('leads.store'), $this->payload(['site' => 'http://spam.test']))->assertCreated();

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_resubmission_reuses_the_lead_and_keeps_the_first_touch_origin(): void
    {
        $this->postJson(route('leads.store'), $this->payload(['utm_source' => 'facebook', 'utm_campaign' => 'primeira']));
        $this->postJson(route('leads.store'), $this->payload([
            'email' => 'ana@example.com',
            'plan' => 'sessao',
            'utm_source' => 'google',
            'utm_campaign' => 'segunda',
        ]))->assertCreated();

        $lead = Lead::sole();
        $this->assertSame('primeira', $lead->utm_campaign);
        $this->assertSame(Channel::Meta, $lead->channel);
        $this->assertSame('sessao', $lead->plan_interest->value);
        $this->assertCount(2, $lead->submissions);
    }

    public function test_submissions_are_rate_limited_per_ip(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('leads.store'), $this->payload(['email' => "lead{$attempt}@example.com"]))->assertCreated();
        }

        $this->postJson(route('leads.store'), $this->payload(['email' => 'lead6@example.com']))->assertTooManyRequests();
    }

    public function test_form_without_javascript_redirects_back_with_success(): void
    {
        $this->withoutVite();

        $this->post(route('leads.store'), $this->payload())
            ->assertRedirect(route('home').'#agendar')
            ->assertSessionHas('lead_sent');

        $this->assertDatabaseCount('leads', 1);
    }
}

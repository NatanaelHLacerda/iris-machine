<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_home_renders_the_sales_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('IRIS MACHINE')
            ->assertSee('Agendar conversa')
            ->assertSee('id="agendar"', false);
    }

    public function test_a_lead_can_be_captured(): void
    {
        $this->post('/leads', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'level' => 'estudante',
            'plan' => 'mentoria',
        ])->assertRedirect(route('home').'#agendar')->assertSessionHas('lead_ok');

        $this->assertDatabaseHas('leads', ['email' => 'ada@example.com', 'plan' => 'mentoria']);
    }

    public function test_same_email_updates_the_existing_lead(): void
    {
        $payload = ['name' => 'Ada', 'email' => 'ada@example.com', 'level' => 'estudante'];

        $this->post('/leads', $payload);
        $this->post('/leads', [...$payload, 'level' => 'junior']);

        $this->assertSame(1, Lead::count());
        $this->assertSame('junior', Lead::first()->level);
    }

    public function test_invalid_leads_are_rejected(): void
    {
        $this->post('/leads', ['name' => '', 'email' => 'nope', 'level' => 'x'])
            ->assertSessionHasErrors(['name', 'email', 'level']);

        $this->post('/leads', [
            'name' => 'Bot', 'email' => 'bot@example.com', 'level' => 'junior', 'site' => 'http://spam',
        ])->assertSessionHasErrors('site');

        $this->assertSame(0, Lead::count());
    }

    public function test_json_submission_returns_created(): void
    {
        $this->postJson('/leads', ['name' => 'Linus', 'email' => 'linus@example.com', 'level' => 'junior'])
            ->assertCreated()
            ->assertJsonStructure(['message']);
    }
}

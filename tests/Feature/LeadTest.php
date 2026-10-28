<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_lead_is_saved_and_the_visitor_is_sent_back_to_the_form(): void
    {
        $this->post(route('leads.store'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'level' => 'estudante',
            'plan' => 'mentoria',
        ])->assertRedirect(route('home').'#agendar')->assertSessionHas('lead_ok');

        $this->assertDatabaseHas('leads', ['email' => 'ada@example.com', 'plan' => 'mentoria']);
    }

    public function test_the_javascript_submission_receives_json(): void
    {
        $this->postJson(route('leads.store'), ['name' => 'Linus', 'email' => 'linus@example.com', 'level' => 'junior'])
            ->assertCreated()
            ->assertJsonStructure(['message']);
    }

    public function test_invalid_data_is_rejected(): void
    {
        $this->postJson(route('leads.store'), ['name' => '', 'email' => 'nope', 'level' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'level']);

        $this->assertSame(0, Lead::count());
    }

    public function test_bots_that_fill_the_honeypot_are_rejected(): void
    {
        $this->postJson(route('leads.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'level' => 'junior',
            'site' => 'http://spam',
        ])->assertJsonValidationErrors('site');

        $this->assertSame(0, Lead::count());
    }
}

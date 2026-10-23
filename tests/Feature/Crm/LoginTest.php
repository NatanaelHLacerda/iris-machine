<?php

namespace Tests\Feature\Crm;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/gestao-crm')->assertRedirect(route('crm.login'));
    }

    public function test_login_page_is_not_indexed(): void
    {
        $this->get(route('crm.login'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_admin_logs_in_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $this->post(route('crm.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('crm.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_show_a_generic_error(): void
    {
        $user = User::factory()->create();

        $this->post(route('crm.login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);

        $this->assertGuest();
    }

    public function test_login_is_blocked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('crm.login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->post(route('crm.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_admin_visiting_login_goes_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('crm.login'))
            ->assertRedirect(route('crm.dashboard'));
    }

    public function test_dashboard_renders_the_crm_shell(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Natanael Hausmano']))
            ->get(route('crm.dashboard'))
            ->assertOk()
            ->assertSee('Gestão CRM')
            ->assertSee('NH');
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('crm.logout'))
            ->assertRedirect(route('crm.login'));

        $this->assertGuest();
    }
}

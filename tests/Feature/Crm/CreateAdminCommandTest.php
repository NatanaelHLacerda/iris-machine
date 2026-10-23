<?php

namespace Tests\Feature\Crm;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_admin(): void
    {
        $this->artisan('crm:create-admin', ['--name' => 'Natanael', '--email' => 'admin@iris.test'])
            ->expectsQuestion('Senha (mín. 8 caracteres)', 'super-secreta')
            ->assertSuccessful();

        $user = User::firstWhere('email', 'admin@iris.test');
        $this->assertTrue(Hash::check('super-secreta', $user->password));
    }

    public function test_it_refuses_a_duplicated_email(): void
    {
        User::factory()->create(['email' => 'admin@iris.test']);

        $this->artisan('crm:create-admin', ['--name' => 'Outro', '--email' => 'admin@iris.test'])
            ->expectsQuestion('Senha (mín. 8 caracteres)', 'super-secreta')
            ->assertFailed();

        $this->assertSame(1, User::count());
    }
}

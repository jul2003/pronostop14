<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\PlayerColorPalette;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_registration_screen_is_disabled(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_initial_setup_screen_can_be_rendered_when_no_user_exists(): void
    {
        $response = $this->get('/initialisation');

        $response->assertStatus(200);
    }

    public function test_initial_setup_creates_the_first_super_admin_and_logs_him_in(): void
    {
        $response = $this->post('/initialisation', [
            'name' => 'Julien Admin',
            'nickname' => 'JA01',
            'email_pro' => 'julien.admin@example.com',
            'email_perso' => null,
            'color' => PlayerColorPalette::colors()[0],
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect(route('admin.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'nickname' => 'JA01',
            'email_pro' => 'julien.admin@example.com',
            'role' => 'super_admin',
        ]);

        $this->assertTrue(User::first()->isSuperAdmin());
    }
}

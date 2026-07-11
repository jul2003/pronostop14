<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered_when_application_has_users(): void
    {
        User::factory()->create();

        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'nickname' => 'AB12',
            'email' => 'ab12@example.com',
            'email_pro' => 'ab12@example.com',
            'email_perso' => null,
        ]);

        $response = $this->post('/login', [
            'login' => 'AB12',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_users_can_authenticate_using_their_professional_email(): void
    {
        $user = User::factory()->create([
            'nickname' => 'CD34',
            'email' => 'cd34@example.com',
            'email_pro' => 'cd34@example.com',
            'email_perso' => null,
        ]);

        $response = $this->post('/login', [
            'login' => 'cd34@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        User::factory()->create([
            'nickname' => 'EF56',
            'email' => 'ef56@example.com',
            'email_pro' => 'ef56@example.com',
            'email_perso' => null,
        ]);

        $this->post('/login', [
            'login' => 'EF56',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}

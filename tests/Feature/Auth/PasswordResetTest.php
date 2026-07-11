<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested_with_login_field(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'nickname' => 'PR01',
            'email' => 'password.reset@example.com',
            'email_pro' => 'password.reset@example.com',
            'email_perso' => null,
        ]);

        $this->post('/forgot-password', [
            'login' => 'password.reset@example.com',
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'nickname' => 'PR02',
            'email' => 'password.screen@example.com',
            'email_pro' => 'password.screen@example.com',
            'email_perso' => null,
        ]);

        $this->post('/forgot-password', [
            'login' => 'password.screen@example.com',
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'nickname' => 'PR03',
            'email' => 'password.valid@example.com',
            'email_pro' => 'password.valid@example.com',
            'email_perso' => null,
        ]);

        $this->post('/forgot-password', [
            'login' => 'password.valid@example.com',
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email_pro,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PlayerColorPalette;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/mon-profil');

        $response->assertOk();
    }

    public function test_player_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create([
            'nickname' => 'AA01',
            'email' => 'old.profile@example.com',
            'email_pro' => 'old.profile@example.com',
            'email_perso' => null,
            'color' => PlayerColorPalette::colors()[0],
        ]);

        $newColor = PlayerColorPalette::colors()[1];

        $response = $this
            ->actingAs($user)
            ->put('/mon-profil', [
                'nickname' => 'BB02',
                'email_pro' => 'new.profile@example.com',
                'email_perso' => null,
                'color' => $newColor,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('BB02', $user->nickname);
        $this->assertSame('new.profile@example.com', $user->email_pro);
        $this->assertNull($user->email_perso);
        $this->assertSame($newColor, $user->color);
    }

    public function test_player_profile_requires_at_least_one_email(): void
    {
        $user = User::factory()->create([
            'nickname' => 'CC03',
            'email' => 'profile.required@example.com',
            'email_pro' => 'profile.required@example.com',
            'email_perso' => null,
            'color' => PlayerColorPalette::colors()[2],
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/mon-profil')
            ->put('/mon-profil', [
                'nickname' => 'CC03',
                'email_pro' => null,
                'email_perso' => null,
                'color' => PlayerColorPalette::colors()[2],
            ]);

        $response
            ->assertSessionHasErrors(['email_pro', 'email_perso'])
            ->assertRedirect('/mon-profil');
    }

    public function test_player_profile_rejects_a_color_outside_the_palette(): void
    {
        $user = User::factory()->create([
            'nickname' => 'DD04',
            'email' => 'profile.color@example.com',
            'email_pro' => 'profile.color@example.com',
            'email_perso' => null,
            'color' => PlayerColorPalette::colors()[3],
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/mon-profil')
            ->put('/mon-profil', [
                'nickname' => 'DD04',
                'email_pro' => 'profile.color@example.com',
                'email_perso' => null,
                'color' => '#123456',
            ]);

        $response
            ->assertSessionHasErrors(['color'])
            ->assertRedirect('/mon-profil');
    }
}

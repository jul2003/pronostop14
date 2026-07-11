<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\PlayerColorPalette;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'name' => fake()->name(),
            'nickname' => strtoupper(fake()->unique()->bothify('??##')),
            'email' => $email,
            'email_pro' => $email,
            'email_perso' => null,
            'email_verified_at' => now(),
            'color' => fake()->unique()->randomElement(PlayerColorPalette::colors()),
            'role' => 'player',
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'super_admin',
        ]);
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'hsk_level' => 1,
            'locale' => 'id',
            'timezone' => 'Asia/Jakarta',
            'is_premium' => false,
            'premium_expires_at' => null,
            'streak_count' => 0,
            'streak_last_date' => null,
            'avatar_url' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the user has a premium subscription.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_premium' => true,
            'premium_expires_at' => now()->addYear(),
        ]);
    }

    /**
     * Set a specific HSK level.
     */
    public function withHskLevel(int $level): static
    {
        return $this->state(fn (array $attributes) => [
            'hsk_level' => $level,
        ]);
    }

    /**
     * Set a learning streak.
     */
    public function withStreak(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'streak_count' => $days,
            'streak_last_date' => now()->subDay(),
        ]);
    }
}

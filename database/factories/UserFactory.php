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
    protected static ?string $passwordHash;
    protected static ?string $plainTextPassword = 'password';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$passwordHash ??= Hash::make(static::$plainTextPassword),
            'surname' => fake()->lastName(),
            'firstname' => fake()->firstName(),
            'othernames' => fake()->name(),
            'is_active' => 1,
            'role' => 'user',
            'ministry' => fake()->numberBetween(1, 100),
            'department' => fake()->numberBetween(1, 100),
            'permissions' => json_encode(['create', 'read', 'update']),
            'remember_token' => Str::random(10),
            'plain_text_password' => static::$plainTextPassword,
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
}

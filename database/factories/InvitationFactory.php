<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'token' => Str::random(64),
            'role' => fake()->randomElement(['Ghost', 'Admin', 'User']),
            'registered_at' => null,
            'expires_at' => now()->addDays(7),
            'invited_by' => User::factory(),
        ];
    }

    //public function withRole(string $role): static
    //{
    //    return $this->state(fn (array $attributes) => [
    //        'role' => $role,
    //    ]);
    //}

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    public function registered(): static
    {
        return $this->state(fn (array $attributes) => [
            'registered_at' => now()->subDays(rand(1, 6)),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'registered_at' => null,
            'expires_at' => now()->addDays(rand(1, 7)),
        ]);
    }
}
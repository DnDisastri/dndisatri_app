<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
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
            // `name` è univoco: nella Gilda i giocatori si riconoscono dal nome.
            'name' => fake()->unique()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // Approvato di default: quasi tutti i test vogliono un account che
            // può già entrare. L'attesa si chiede a parte, con `unapproved()`.
            'approved_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
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

    /** Registrato ma non ancora approvato: non entra. */
    public function unapproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->withRole(User::ROLE_ADMIN);
    }

    public function dm(): static
    {
        return $this->withRole(User::ROLE_DM);
    }

    public function player(): static
    {
        return $this->withRole(User::ROLE_PLAYER);
    }

    private function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            $user->assignRole(Role::findOrCreate($role, 'web'));
        });
    }
}

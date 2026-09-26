<?php

namespace Database\Factories;

use App\Models\Subclass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subclass>
 */
class SubclassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class' => 'Guerriero',
            // Univoco: classe più nome hanno un vincolo, e i test ne creano
            // più d'una per la stessa classe.
            'name' => 'Cammino di '.$this->faker->unique()->firstName(),
            'description' => 'Una specializzazione inventata per le prove.',
            'third_caster' => false,
            'is_homebrew' => true,
            'position' => 99,
        ];
    }

    public function of(string $class): static
    {
        return $this->state(fn () => ['class' => $class]);
    }

    public function fromTheBook(): static
    {
        return $this->state(fn () => ['is_homebrew' => false]);
    }

    public function thirdCaster(): static
    {
        return $this->state(fn () => ['third_caster' => true]);
    }
}

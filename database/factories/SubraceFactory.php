<?php

namespace Database\Factories;

use App\Models\Subrace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subrace>
 */
class SubraceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'race' => 'Elfo',
            // Univoco: razza più nome hanno un vincolo.
            'name' => 'Elfo di '.$this->faker->unique()->firstName(),
            'description' => 'Una variante inventata per le prove.',
            'asi' => ['int' => 1],
            'speed' => null,
            'traits' => '+1 Intelligenza.',
            'is_homebrew' => true,
            'position' => 99,
        ];
    }

    public function of(string $race): static
    {
        return $this->state(fn () => ['race' => $race]);
    }

    public function fromTheBook(): static
    {
        return $this->state(fn () => ['is_homebrew' => false]);
    }

    /** Come le discendenze draconiche e le etnie: nessun bonus. */
    public function withoutBonuses(): static
    {
        return $this->state(fn () => ['asi' => []]);
    }
}

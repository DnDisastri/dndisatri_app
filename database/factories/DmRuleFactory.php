<?php

namespace Database\Factories;

use App\Models\DmRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DmRule>
 */
class DmRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category' => fake('it_IT')->randomElement(['Ricompense', 'Sessioni', 'Personaggi']),
            'title' => rtrim(fake('it_IT')->sentence(4), '.'),
            'body' => fake('it_IT')->paragraph(2),
            'position' => 0,
        ];
    }

    public function inSection(string $category): static
    {
        return $this->state(fn () => ['category' => $category]);
    }
}

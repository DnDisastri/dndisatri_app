<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category' => fake('it_IT')->randomElement(['Eroi', 'Incarichi', 'Mercato']),
            'question' => rtrim(fake('it_IT')->sentence(6), '.').'?',
            'answer' => fake('it_IT')->paragraph(3),
            'position' => 0,
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    public function inSection(string $category): static
    {
        return $this->state(fn () => ['category' => $category]);
    }
}

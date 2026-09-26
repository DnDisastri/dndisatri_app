<?php

namespace Database\Factories;

use App\Enums\TutorialIllustration;
use App\Models\TutorialStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorialStep>
 */
class TutorialStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'illustration' => fake()->randomElement(TutorialIllustration::cases()),
            'title' => fake('it_IT')->sentence(3),
            'body' => fake('it_IT')->paragraph(2),
            'position' => 0,
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}

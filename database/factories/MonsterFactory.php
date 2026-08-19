<?php

namespace Database\Factories;

use App\Models\Monster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monster>
 */
class MonsterFactory extends Factory
{
    protected $model = Monster::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Goblin', 'Hobgoblin', 'Orso bruno', 'Lupo', 'Scheletro', 'Zombi']),
            'hp' => fake()->numberBetween(5, 60),
            'ac' => fake()->numberBetween(10, 18),
            'speed' => '9 m',
            'attacks' => [
                ['nome' => 'Attacco', 'bonus' => '+4', 'danni' => '1d6+2'],
            ],
            'traits' => null,
        ];
    }
}

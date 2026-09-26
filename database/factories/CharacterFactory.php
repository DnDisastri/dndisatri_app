<?php

namespace Database\Factories;

use App\Models\Character;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Character>
 */
class CharacterFactory extends Factory
{
    public function definition(): array
    {
        $class = fake()->randomElement(array_keys(config('dnd.classes.list')));

        return [
            'user_id' => User::factory()->player(),
            'name' => fake('it_IT')->firstName(),
            'class' => $class,
            'subclass' => null,
            'race' => fake()->randomElement(array_keys(config('dnd.species'))),
            'background' => fake()->randomElement(array_keys(config('dnd.backgrounds.list'))),
            'level' => 1,
            'hit_die' => config("dnd.classes.list.{$class}.hitDie"),
            'str' => 10, 'dex' => 14, 'con' => 14,
            'int' => 10, 'wis' => 12, 'cha' => 10,
            'speed' => 9,
            'hp_max' => 10,
            'hp_current' => 10,
            'hp_temp' => 0,
            'gp' => 15,
            'saving_throws' => [],
            'skills' => [],
            'spell_slots_used' => [],
            'died_at' => null,
        ];
    }

    /** Personaggio caduto: entra nel memoriale e libera lo slot del giocatore. */
    public function fallen(): static
    {
        return $this->state(fn () => ['died_at' => now()]);
    }

    public function ownedBy(User $player): static
    {
        return $this->state(fn () => ['user_id' => $player->getKey()]);
    }

    public function level(int $level): static
    {
        return $this->state(fn () => ['level' => $level]);
    }
}

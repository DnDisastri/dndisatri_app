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
    /** Le classi del manuale con i loro dadi vita: niente database per scegliere a caso. */
    private const CLASSI = [
        'Barbaro' => 12, 'Bardo' => 8, 'Chierico' => 8, 'Druido' => 8, 'Guerriero' => 10, 'Ladro' => 8,
        'Mago' => 6, 'Monaco' => 8, 'Paladino' => 10, 'Ranger' => 10, 'Stregone' => 6, 'Warlock' => 8,
    ];

    private const SPECIE = ['Umano', 'Nano', 'Elfo', 'Halfling', 'Dragonide', 'Gnomo', 'Mezzorco', 'Tiefling'];

    public function definition(): array
    {
        $class = fake()->randomElement(array_keys(self::CLASSI));

        return [
            'user_id' => User::factory()->player(),
            'name' => fake('it_IT')->firstName(),
            'class' => $class,
            'subclass' => null,
            'race' => fake()->randomElement(self::SPECIE),
            'background' => fake()->randomElement(array_keys(config('dnd.backgrounds.list'))),
            'level' => 1,
            'hit_die' => self::CLASSI[$class],
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

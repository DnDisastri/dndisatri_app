<?php

namespace Database\Factories;

use App\Models\Character;
use App\Models\CharacterSpell;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterSpell>
 */
class CharacterSpellFactory extends Factory
{
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'name' => 'Dardo Incantato',
            'level' => 1,
            'description' => null,
        ];
    }
}

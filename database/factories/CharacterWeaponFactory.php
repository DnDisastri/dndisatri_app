<?php

namespace Database\Factories;

use App\Domain\Dnd\Ability;
use App\Models\Character;
use App\Models\CharacterWeapon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterWeapon>
 */
class CharacterWeaponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'name' => 'Spada Lunga',
            'attack_ability' => Ability::Str,
            'weapon_bonus' => 0,
            'damage' => '1d8+3',
        ];
    }
}

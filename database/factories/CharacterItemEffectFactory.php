<?php

namespace Database\Factories;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemEffectMode;
use App\Models\Character;
use App\Models\CharacterItemEffect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterItemEffect>
 */
class CharacterItemEffectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'name' => 'Amuleto della Salute',
            'ability' => Ability::Con,
            'mode' => ItemEffectMode::Set,
            'value' => 19,
        ];
    }

    public function bonus(Ability $ability, int $value, string $name = 'Oggetto magico'): static
    {
        return $this->state(fn () => [
            'name' => $name,
            'ability' => $ability,
            'mode' => ItemEffectMode::Bonus,
            'value' => $value,
        ]);
    }

    public function setTo(Ability $ability, int $value, string $name = 'Oggetto magico'): static
    {
        return $this->state(fn () => [
            'name' => $name,
            'ability' => $ability,
            'mode' => ItemEffectMode::Set,
            'value' => $value,
        ]);
    }
}

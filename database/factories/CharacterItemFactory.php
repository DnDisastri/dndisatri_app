<?php

namespace Database\Factories;

use App\Enums\EquipmentSlot;
use App\Models\Character;
use App\Models\CharacterItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterItem>
 */
class CharacterItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'name' => 'Corda di Canapa',
            'category' => 'Equipaggiamento',
            'qty' => 1,
            'value' => 1,
            'details' => null,
            'equipped_slot' => null,
        ];
    }

    public function named(string $name, ?string $category = null): static
    {
        return $this->state(fn () => array_filter([
            'name' => $name,
            'category' => $category,
        ]));
    }

    public function equippedAs(EquipmentSlot $slot): static
    {
        return $this->state(fn () => ['equipped_slot' => $slot]);
    }

    /** Armatura indossata, presa dal catalogo dei dati di gioco. */
    public function armor(string $name = 'Cotta di Maglia'): static
    {
        return $this->state(fn () => [
            'name' => $name,
            'category' => 'Armature',
            'equipped_slot' => EquipmentSlot::Armor,
        ]);
    }

    public function shield(string $name = 'Scudo'): static
    {
        return $this->state(fn () => [
            'name' => $name,
            'category' => 'Armature',
            'equipped_slot' => EquipmentSlot::Shield,
        ]);
    }
}

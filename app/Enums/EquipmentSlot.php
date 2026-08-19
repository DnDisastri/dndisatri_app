<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Gli slot di equipaggiamento. Uno solo per slot, garantito dall'indice
 * univoco su `character_items`.
 *
 * Gli oggetti magici **non stanno qui**: non si indossano in un posto preciso,
 * ci si va in sintonia, e se ne tengono tre. Vedi `attuned` sull'inventario.
 */
enum EquipmentSlot: string
{
    case Weapon = 'weapon';
    case Armor = 'armor';
    case Shield = 'shield';

    public function label(): string
    {
        return match ($this) {
            self::Weapon => 'Arma',
            self::Armor => 'Armatura',
            self::Shield => 'Scudo',
        };
    }

    /** L'oggetto è idoneo a questo slot secondo i dati di gioco? */
    public function accepts(string $itemName): bool
    {
        return match ($this) {
            self::Weapon => config("dnd.combat.weapons.{$itemName}") !== null,
            self::Armor => config("dnd.combat.armor.{$itemName}") !== null,
            self::Shield => config("dnd.combat.shields.{$itemName}") !== null,
        };
    }
}

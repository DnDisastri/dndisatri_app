<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Enums\EquipmentSlot;

/**
 * Classe Armatura e iniziativa: si ricalcolano sempre dai punteggi EFFICACI e
 * dall'equipaggiamento indossato.
 *
 * Nella vecchia applicazione esistevano anche colonne `ac` e `initiative`
 * salvate, che però nessuno leggeva. Qui non esistono proprio: il valore è
 * sempre e solo quello calcolato.
 */
final class ArmorClass
{
    public static function initiative(AbilityScores $effective): int
    {
        return $effective->modifier(Ability::Dex);
    }

    /** `$magicBonus` è la somma dei +N di armatura e scudo indossati. */
    public static function compute(AbilityScores $effective, ?string $armor = null, ?string $shield = null, int $magicBonus = 0): int
    {
        $catalogo = app(ItemCatalogue::class);
        $dex = $effective->modifier(Ability::Dex);
        $worn = $catalogo->findOf(EquipmentSlot::Armor, $armor);

        // Senza armatura, o con un nome che non è a catalogo, 10 + DES. Il
        // tetto vuoto è «tutta la Destrezza» (leggere), 0 è «niente» (pesanti).
        $ac = $worn === null
            ? 10 + $dex
            : (int) $worn->armor_base + match ($worn->dex_cap) {
                null => $dex,
                // Le pesanti ignorano la Destrezza, anche negativa.
                0 => 0,
                default => min($dex, $worn->dex_cap),
            };

        return $ac + (int) $catalogo->findOf(EquipmentSlot::Shield, $shield)?->shield_bonus + $magicBonus;
    }
}

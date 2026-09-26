<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

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

    public static function compute(AbilityScores $effective, ?string $armor = null, ?string $shield = null): int
    {
        $dex = $effective->modifier(Ability::Dex);
        $worn = $armor === null ? null : config("dnd.combat.armor.{$armor}");

        $ac = match ($worn['type'] ?? null) {
            'light' => $worn['base'] + $dex,
            // L'armatura media limita il contributo della Destrezza a +2.
            'medium' => $worn['base'] + min($dex, 2),
            'heavy' => $worn['base'],
            // Senza armatura, o con un nome che non è a catalogo.
            default => 10 + $dex,
        };

        return $ac + ($shield === null ? 0 : (int) config("dnd.combat.shields.{$shield}", 0));
    }
}

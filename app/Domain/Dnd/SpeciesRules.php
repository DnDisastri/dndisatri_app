<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/** Le scelte che alcune specie fanno fare in creazione, oltre ai +1 di `PointBuy`. */
final class SpeciesRules
{
    /** Quante competenze in abilità a scelta concede la specie. */
    public static function skillChoices(?string $species): int
    {
        return match ($species) {
            'Umano (Variante)' => 1,
            'Mezzelfo' => 2,
            default => 0,
        };
    }

    /** Un talento al primo livello: solo l'Umano Variante. */
    public static function grantsFeat(?string $species): bool
    {
        return $species === 'Umano (Variante)';
    }
}

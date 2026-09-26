<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/**
 * Tutto ciò che dipende dal livello del personaggio.
 */
final class Progression
{
    public const MIN_LEVEL = 1;

    public const MAX_LEVEL = 20;

    /** Tetto ai punteggi di caratteristica raggiungibile tramite ASI. */
    public const ASI_SCORE_CAP = 20;

    /** Bonus di competenza: +2 al livello 1, +6 al 17. */
    public static function proficiencyBonus(int $level): int
    {
        return (int) floor(($level - 1) / 4) + 2;
    }

    /** I livelli a cui si sceglie fra aumento di caratteristica e talento. */
    public static function isAsiLevel(int $level): bool
    {
        return in_array($level, config('dnd.character.asi_levels', []), true);
    }

    /**
     * Livello a cui la classe sceglie la sottoclasse: prima non è consentito.
     */
    public static function subclassLevel(?string $class): int
    {
        return match ($class) {
            'Chierico', 'Stregone', 'Warlock' => 1,
            'Druido', 'Mago' => 2,
            default => 3,
        };
    }

    /**
     * Quante abilità possono avere Esperto (doppia competenza) a questo
     * livello. Solo Ladro e Bardo: tutte le altre classi zero.
     */
    public static function expertiseCount(?string $class, int $level = 1): int
    {
        return match ($class) {
            'Ladro' => $level >= 6 ? 4 : 2,
            'Bardo' => match (true) {
                $level >= 10 => 4,
                $level >= 3 => 2,
                default => 0,
            },
            default => 0,
        };
    }
}

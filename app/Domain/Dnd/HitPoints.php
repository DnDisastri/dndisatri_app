<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/**
 * Punti ferita.
 *
 * È la parte più delicata del dominio: l'aumento retroattivo dovuto a un ASI
 * che alza il modificatore di Costituzione ha due dettagli facili da sbagliare,
 * entrambi ripresi dal comportamento della vecchia applicazione e fissati nei
 * test. Vedi docs/PIANO.md §1-bis.
 */
final class HitPoints
{
    /**
     * PF guadagnati salendo di un livello, metodo "media" 5e:
     * (dado / 2 + 1) + modificatore di Costituzione, mai meno di 1.
     */
    public static function gainForLevel(int $hitDie, int $constitutionModifier): int
    {
        return max(1, intdiv($hitDie, 2) + 1 + $constitutionModifier);
    }

    /**
     * PF retroattivi quando un ASI fa salire il modificatore di Costituzione:
     * i livelli già acquisiti valgono ognuno un PF in più per ogni punto di
     * modificatore guadagnato.
     *
     * Due dettagli deliberati:
     *
     * - il moltiplicatore è `$newLevel - 1`, cioè il livello DOPO il
     *   passaggio. Salendo dal 7° all'8° con COS da 15 a 16 si guadagnano 7 PF
     *   retroattivi, non 6.
     * - un calo del modificatore non toglie PF (`max(0, ...)`). Con l'ASI non
     *   può succedere, ma la regola resta esplicita.
     */
    public static function retroactiveGain(int $constitutionModifierDelta, int $newLevel): int
    {
        return max(0, $constitutionModifierDelta) * ($newLevel - 1);
    }

    /**
     * PF totali guadagnati in un passaggio di livello, ASI compreso.
     *
     * L'ordine conta: l'ASI si applica ai punteggi PRIMA di calcolare i PF del
     * nuovo livello, quindi il guadagno del livello usa già il modificatore
     * aggiornato.
     */
    public static function onLevelUp(
        int $hitDie,
        int $constitutionModifierBefore,
        int $constitutionModifierAfter,
        int $newLevel,
    ): int {
        return self::gainForLevel($hitDie, $constitutionModifierAfter)
            + self::retroactiveGain($constitutionModifierAfter - $constitutionModifierBefore, $newLevel);
    }

    /**
     * PF massimi EFFICACI: se un oggetto magico altera la Costituzione, il
     * massimo sale o scende di (Δ modificatore × livello) finché l'oggetto
     * resta equipaggiato. Il valore salvato non viene toccato, così togliendo
     * l'oggetto tutto torna com'era.
     */
    public static function effectiveMax(int $storedMax, AbilityScores $base, AbilityScores $effective, int $level): int
    {
        $delta = $effective->modifier(Ability::Con) - $base->modifier(Ability::Con);

        return $storedMax + $delta * $level;
    }
}

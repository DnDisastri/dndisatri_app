<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Models\Species;

/** Cosa concede una specie in creazione. I dati vengono dalla tabella `species`. */
final class SpeciesRules
{
    private static function of(?string $species): ?Species
    {
        return app(SpeciesCatalogue::class)->find($species);
    }

    /** @return list<string> */
    public static function names(): array
    {
        return app(SpeciesCatalogue::class)->names();
    }

    public static function exists(?string $species): bool
    {
        return self::of($species) !== null;
    }

    /** @return array<string,int> i bonus fissi; `all` vale per ogni caratteristica */
    public static function asi(?string $species): array
    {
        return self::of($species)?->asi ?? [];
    }

    public static function speed(?string $species): float
    {
        return self::of($species)?->speed ?? 9.0;
    }

    public static function traits(?string $species): ?string
    {
        return self::of($species)?->traits;
    }

    public static function description(?string $species): ?string
    {
        return self::of($species)?->description;
    }

    /** Quanti +1 a scelta concede (Umano Variante, Mezzelfo). */
    public static function freeBonuses(?string $species): int
    {
        return self::of($species)?->free_bonuses ?? 0;
    }

    /** Quante competenze in abilità a scelta concede la specie. */
    public static function skillChoices(?string $species): int
    {
        return self::of($species)?->skill_choices ?? 0;
    }

    /** Un talento al primo livello, come l'Umano Variante. */
    public static function grantsFeat(?string $species): bool
    {
        return (bool) self::of($species)?->grants_feat;
    }
}

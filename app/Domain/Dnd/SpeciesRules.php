<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Enums\RulesEdition;
use App\Models\Species;

/**
 * Cosa concede una specie in creazione. I dati vengono dalla tabella `species`;
 * lo stesso nome può esistere in entrambe le edizioni, quindi conta anche quella.
 */
final class SpeciesRules
{
    private static function of(?string $species, RulesEdition $edition): ?Species
    {
        return app(SpeciesCatalogue::class)->find($species, $edition);
    }

    /** @return list<string> */
    public static function names(RulesEdition $edition = RulesEdition::Classic): array
    {
        return app(SpeciesCatalogue::class)->names($edition);
    }

    public static function exists(?string $species, RulesEdition $edition = RulesEdition::Classic): bool
    {
        return self::of($species, $edition) !== null;
    }

    /** @return array<string,int> i bonus fissi; `all` vale per ogni caratteristica */
    public static function asi(?string $species, RulesEdition $edition = RulesEdition::Classic): array
    {
        return self::of($species, $edition)?->asi ?? [];
    }

    public static function speed(?string $species, RulesEdition $edition = RulesEdition::Classic): float
    {
        return self::of($species, $edition)?->speed ?? 9.0;
    }

    public static function traits(?string $species, RulesEdition $edition = RulesEdition::Classic): ?string
    {
        return self::of($species, $edition)?->traits;
    }

    public static function description(?string $species, RulesEdition $edition = RulesEdition::Classic): ?string
    {
        return self::of($species, $edition)?->description;
    }

    /** Quanti +1 a scelta concede (Umano Variante, Mezzelfo). */
    public static function freeBonuses(?string $species, RulesEdition $edition = RulesEdition::Classic): int
    {
        return self::of($species, $edition)?->free_bonuses ?? 0;
    }

    /** Quante competenze in abilità a scelta concede la specie. */
    public static function skillChoices(?string $species, RulesEdition $edition = RulesEdition::Classic): int
    {
        return self::of($species, $edition)?->skill_choices ?? 0;
    }

    /** Un talento al primo livello, come l'Umano Variante o l'Umano 2024. */
    public static function grantsFeat(?string $species, RulesEdition $edition = RulesEdition::Classic): bool
    {
        return (bool) self::of($species, $edition)?->grants_feat;
    }
}

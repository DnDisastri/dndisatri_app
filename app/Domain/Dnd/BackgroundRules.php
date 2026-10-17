<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Enums\RulesEdition;
use App\Models\Background;
use InvalidArgumentException;

/** Cosa concede un background in creazione. I dati vengono dalla tabella `backgrounds`. */
final class BackgroundRules
{
    public static function of(?string $background, RulesEdition $edition = RulesEdition::Classic): ?Background
    {
        return app(BackgroundCatalogue::class)->find($background, $edition);
    }

    /** @return list<string> */
    public static function names(RulesEdition $edition = RulesEdition::Classic): array
    {
        return app(BackgroundCatalogue::class)->all($edition)->keys()->all();
    }

    public static function exists(?string $background, RulesEdition $edition = RulesEdition::Classic): bool
    {
        return self::of($background, $edition) !== null;
    }

    /** Quante abilità lascia scegliere: zero per quelli del manuale, due per «Personalizzato». */
    public static function freeSkills(?string $background, RulesEdition $edition = RulesEdition::Classic): int
    {
        return self::of($background, $edition)?->free_skills ?? 0;
    }

    /**
     * Le regole 2024: +2 e +1 a due delle tre caratteristiche del background,
     * oppure +1 a tutte e tre. Restituisce i bonus ripuliti, o spiega perché no.
     *
     * @param  array<string,int|string>  $bonuses
     * @return array<string,int>
     */
    public static function validateBonuses(Background $background, array $bonuses): array
    {
        $opzioni = $background->ability_options ?? [];
        $bonuses = collect($bonuses)->map(fn ($b) => (int) $b)->filter()->all();

        foreach (array_keys($bonuses) as $ability) {
            if (! in_array($ability, $opzioni, true)) {
                throw new InvalidArgumentException("Il background «{$background->name}» non aumenta questa caratteristica.");
            }
        }

        $valori = array_values($bonuses);
        sort($valori);

        if ($valori !== [1, 2] && $valori !== [1, 1, 1]) {
            throw new InvalidArgumentException('Dal background: +2 e +1 a due caratteristiche diverse, oppure +1 a tutte e tre.');
        }

        return $bonuses;
    }
}

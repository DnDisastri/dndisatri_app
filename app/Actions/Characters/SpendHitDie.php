<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Domain\Dnd\Ability;
use App\Models\Character;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Spendere un dado vita, in due modi:
 *
 * - in un riposo breve: il giocatore tira il dado vero e qui arriva il
 *   risultato, a cui si somma il modificatore di Costituzione (mai sotto zero);
 * - per un privilegio di classe: si passa `null`, la riserva cala e i PF no.
 *
 * Non si controlla che sia in corso un riposo, solo che il dado ci sia.
 */
final class SpendHitDie
{
    public function handle(Character $character, ?int $rolled): Character
    {
        if ($character->hitDiceLeft() < 1) {
            throw new RuntimeException(
                'Non ti restano dadi vita: tornano col riposo lungo, metà per volta.'
            );
        }

        // Speso per un privilegio di classe: la riserva cala, i punti ferita no.
        if ($rolled === null) {
            $character->forceFill(['hit_dice_used' => (int) $character->hit_dice_used + 1])->save();

            return $character;
        }

        if ($rolled < 1) {
            throw new RuntimeException('Scrivi quanto hai fatto col dado.');
        }

        if ($rolled > $character->hit_die) {
            throw new RuntimeException(
                "Un d{$character->hit_die} non fa {$rolled}."
            );
        }

        $recuperati = max(0, $rolled + $character->effectiveScores()->modifier(Ability::Con));

        return DB::transaction(function () use ($character, $recuperati) {
            $character->forceFill(['hit_dice_used' => (int) $character->hit_dice_used + 1])->save();

            return app(AdjustHitPoints::class)->heal($character, $recuperati);
        });
    }
}
